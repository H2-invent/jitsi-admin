<?php

namespace App\Tests\Service;

use App\Message\RecordingUploadedMessage;
use App\Entity\Recording;
use App\Entity\UploadedRecording;
use App\Repository\RecordingRepository;
use App\Repository\RoomsRepository;
use App\Service\MailerService;
use App\Service\RecordingService;
use App\Service\Result\Error\RecordingFinalizeError;
use App\Service\Result\Error\RecordingUploadError;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\Adapter\Local as GaufretteLocalAdapter;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Gaufrette\FilesystemInterface as GaufretteFilesystemInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RecordingServiceTest extends KernelTestCase
{
    private string $projectDir;
    private string $recordingDir;
    private ?GaufretteFilesystemInterface $recordingFilesystem = null;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/jitsi-admin-recording-' . uniqid();
        $this->recordingDir = sys_get_temp_dir() . '/jitsi-admin-recording-fs-' . uniqid();
        (new Filesystem())->mkdir($this->recordingDir);
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        if (is_dir($this->projectDir)) {
            $filesystem->remove($this->projectDir);
        }
        if (is_dir($this->recordingDir)) {
            $filesystem->remove($this->recordingDir);
        }
        $this->recordingFilesystem = null;
        parent::tearDown();
    }

    private function isolateChunkStorage(): RecordingService
    {
        $container = self::getContainer();
        $this->recordingFilesystem = new GaufretteFilesystem(new GaufretteLocalAdapter($this->recordingDir));

        return new RecordingService(
            $this->projectDir,
            new Filesystem(),
            $this->recordingFilesystem,
            $container->get(RecordingRepository::class),
            $container->get(MessageBusInterface::class),
            $container->get(EntityManagerInterface::class),
            $container->get(MailerService::class),
            $container->get(TranslatorInterface::class),
            $container->get(Environment::class),
        );
    }

    private function chunkFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'chunk');
        file_put_contents($path, 'chunk-data');
        return new UploadedFile($path, 'chunk.txt', 'text/plain', null, true);
    }

    public function testIncompleteUploadWritesChunkWithoutDispatch(): void
    {
        self::bootKernel();
        $transport = new InMemoryTransport();
        self::getContainer()->set('messenger.transport.async_slow', $transport);
        $recordingId = 'incomplete-' . uniqid();
        $service = $this->isolateChunkStorage();

        $result = $service->saveChunk(0, 2, $recordingId, $this->chunkFile());

        self::assertTrue($result->isFailure());
        self::assertSame(RecordingUploadError::UPLOAD_INCOMPLETE, $result->getErrorType());
        self::assertFileExists($this->projectDir . '/upload_chunks/' . $recordingId . '/chunk_0');
        self::assertCount(0, $transport->getSent());
    }

    public function testCompleteUploadWritesChunkAndDispatchesMessage(): void
    {
        self::bootKernel();
        $transport = new InMemoryTransport();
        self::getContainer()->set('messenger.transport.async_slow', $transport);
        $recordingId = 'complete-' . uniqid();
        $service = $this->isolateChunkStorage();

        $result = $service->saveChunk(0, 1, $recordingId, $this->chunkFile());

        self::assertTrue($result->isSuccess());
        self::assertFileExists($this->projectDir . '/upload_chunks/' . $recordingId . '/chunk_0');
        self::assertCount(1, $transport->getSent());
        $message = $transport->getSent()[0]->getMessage();
        self::assertInstanceOf(RecordingUploadedMessage::class, $message);
        self::assertSame($recordingId, $message->getRecordingId());
    }

    public function testCompleteUploadAfterMultipleChunksDispatchesOnce(): void
    {
        self::bootKernel();
        $transport = new InMemoryTransport();
        self::getContainer()->set('messenger.transport.async_slow', $transport);
        $recordingId = 'multi-' . uniqid();
        $service = $this->isolateChunkStorage();

        $first = $service->saveChunk(0, 2, $recordingId, $this->chunkFile());
        self::assertTrue($first->isFailure());
        self::assertCount(0, $transport->getSent());

        $second = $service->saveChunk(1, 2, $recordingId, $this->chunkFile());

        self::assertTrue($second->isSuccess());
        self::assertFileExists($this->projectDir . '/upload_chunks/' . $recordingId . '/chunk_0');
        self::assertFileExists($this->projectDir . '/upload_chunks/' . $recordingId . '/chunk_1');
        self::assertCount(1, $transport->getSent());
        self::assertSame($recordingId, $transport->getSent()[0]->getMessage()->getRecordingId());
    }

    public function testFinalizeUploadWithoutRecordingFails(): void
    {
        self::bootKernel();

        $result = $this->isolateChunkStorage()->finalizeUpload('missing-' . uniqid());

        self::assertTrue($result->isFailure());
        self::assertSame(RecordingFinalizeError::NO_RECORDING_FOUND, $result->getErrorType());
    }

    public function testFinalizeUploadWithoutChunksFails(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $uid = 'final-empty-' . uniqid();

        $recording = (new Recording())->setUid($uid)->setRoom($room)->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($recording);
        $entityManager->flush();

        (new Filesystem())->mkdir($this->projectDir . '/upload_chunks/' . $uid);

        $result = $this->isolateChunkStorage()->finalizeUpload($uid);

        self::assertTrue($result->isFailure());
        self::assertSame(RecordingFinalizeError::NO_CHUNKS_FOUND, $result->getErrorType());
    }

    public function testFinalizeUploadAssemblesChunksAndCreatesUploadedRecording(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $container->set('messenger.transport.async_slow', new InMemoryTransport());
        $entityManager = $container->get(EntityManagerInterface::class);
        $room = $container->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $uid = 'final-ok-' . uniqid();

        $recording = (new Recording())->setUid($uid)->setRoom($room)->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($recording);
        $entityManager->flush();

        $tempDir = $this->projectDir . '/upload_chunks/' . $uid;
        (new Filesystem())->mkdir($tempDir);
        file_put_contents($tempDir . '/chunk_0', 'first-part');
        file_put_contents($tempDir . '/chunk_1', 'second-part');

        $result = $this->isolateChunkStorage()->finalizeUpload($uid);

        $filename = null;
        try {
            self::assertTrue($result->isSuccess());
            self::assertDirectoryDoesNotExist($tempDir);

            $uploaded = $entityManager->getRepository(UploadedRecording::class)->findOneBy(['room' => $room]);
            self::assertNotNull($uploaded);
            $filename = $uploaded->getFilename();
            self::assertSame('video/mp4', $uploaded->getType());
            self::assertSame($room->getId(), $uploaded->getRoom()->getId());
            self::assertTrue($this->recordingFilesystem->has($filename));
        } finally {
            if ($filename !== null && $this->recordingFilesystem !== null && $this->recordingFilesystem->has($filename)) {
                $this->recordingFilesystem->delete($filename);
            }
        }
    }
}
