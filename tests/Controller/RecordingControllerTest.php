<?php

namespace App\Tests\Controller;

use App\Controller\RecordingController;
use App\Entity\UploadedRecording;
use App\Repository\RecordingRepository;
use App\Repository\RoomsRepository;
use App\Repository\UploadedRecordingRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use App\Service\RecordingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RecordingControllerTest extends WebTestCase
{
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                }
                rmdir($dir);
            }
        }
        $this->tempDirs = [];
        parent::tearDown();
    }

    private function recordingToken(): string
    {
        return $_ENV['RECORDING_UPLOAD_TOKEN'];
    }

    private function isolateChunkStorage(): void
    {
        $projectDir = sys_get_temp_dir() . '/jitsi-admin-test-' . uniqid();
        $container = self::getContainer();
        $container->set(RecordingService::class, new RecordingService(
            $projectDir,
            new \Symfony\Component\Filesystem\Filesystem(),
            $container->get('gaufrette.recording_fs_filesystem'),
            $container->get(RecordingRepository::class),
            $container->get(MessageBusInterface::class),
            $container->get(EntityManagerInterface::class),
            $container->get(MailerService::class),
            $container->get(TranslatorInterface::class),
            $container->get(Environment::class),
        ));
        $this->tempDirs[] = $projectDir;
    }

    public function testUploadRejectsInvalidBearerToken(): void
    {
        $client = static::createClient();
        $client->loginUser(self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']));
        $client->request('POST', '/recording/upload', [], [], ['HTTP_AUTHORIZATION' => 'Bearer wrong']);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUploadRejectsMissingBearerToken(): void
    {
        $client = static::createClient();
        $client->loginUser(self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']));
        $client->request('POST', '/recording/upload');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUploadRejectsMissingChunkIndex(): void
    {
        $client = static::createClient();
        $client->loginUser(self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']));
        $client->request('POST', '/recording/upload', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->recordingToken()]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(['error' => 'Chunk index is missing'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testUploadReportsIncompleteUpload(): void
    {
        $client = static::createClient();
        $client->loginUser(self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']));
        $this->isolateChunkStorage();
        $recordingId = 'test-incomplete-' . uniqid();
        $tmpFile = tempnam(sys_get_temp_dir(), 'chunk');
        file_put_contents($tmpFile, 'chunk-data');

        $client->request(
            'POST',
            '/recording/upload',
            ['chunk_index' => 0, 'total_chunks' => 2, 'recording_id' => $recordingId],
            ['file' => new UploadedFile($tmpFile, 'chunk.txt', 'text/plain', null, true)],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->recordingToken()]
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(['status' => 'Chunk received'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testUploadStoresCompleteRecording(): void
    {
        $client = static::createClient();
        $client->loginUser(self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']));
        $this->isolateChunkStorage();
        $recordingId = 'test-complete-' . uniqid();
        $tmpFile = tempnam(sys_get_temp_dir(), 'chunk');
        file_put_contents($tmpFile, 'chunk-data');

        $client->request(
            'POST',
            '/recording/upload',
            ['chunk_index' => 0, 'total_chunks' => 1, 'recording_id' => $recordingId],
            ['file' => new UploadedFile($tmpFile, 'chunk.txt', 'text/plain', null, true)],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->recordingToken()]
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(['status' => 'File uploaded successfully'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testModalRendersForModerator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/recordings/modal/' . $room->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testDownloadStreamsRecording(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $filename = 'recording_download_' . uniqid() . '.txt';
        $this->createUploadedRecording($filename, 'text/plain', 'TestMeeting: 1');
        $client->loginUser($user);

        try {
            $client->request('GET', '/room/recordings/download/' . $filename);

            $this->assertResponseIsSuccessful();
            $this->assertStringStartsWith('text/plain', $client->getResponse()->headers->get('Content-Type'));
            $this->assertSame('attachment; filename="TestMeeting: 1.txt"', $client->getResponse()->headers->get('Content-Disposition'));
        } finally {
            self::getContainer()->get('gaufrette.recording_fs_filesystem')->delete($filename);
        }
    }

    public function testDownloadReturnsNotFoundForUnknownFile(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/recordings/download/does-not-exist.txt');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(['error' => 'File not found in database'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testDownloadRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $filename = 'recording_foreign_' . uniqid() . '.txt';
        $this->createUploadedRecording($filename, 'text/plain', 'TestMeeting: 1');
        $client->loginUser($user);

        try {
            $client->request('GET', '/room/recordings/download/' . $filename);
            $this->assertResponseStatusCodeSame(500);
        } finally {
            self::getContainer()->get('gaufrette.recording_fs_filesystem')->delete($filename);
        }
    }

    public function testDownloadForFastConference(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $filename = 'recording_fast_' . uniqid() . '.txt';
        $this->createUploadedRecording($filename, 'text/plain', 'TestMeeting: 1');
        $client->loginUser($user);

        try {
            $client->request('GET', '/room/recordings/download-fastconference/' . $room->getId());

            $this->assertResponseIsSuccessful();
            $this->assertStringStartsWith('text/plain', $client->getResponse()->headers->get('Content-Type'));
        } finally {
            self::getContainer()->get('gaufrette.recording_fs_filesystem')->delete($filename);
        }
    }

    public function testDownloadForFastConferenceWithoutRecording(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $client->loginUser($user);

        $client->request('GET', '/room/recordings/download-fastconference/' . $room->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testRemoveDeletesRecording(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $filename = 'recording_remove_' . uniqid() . '.txt';
        $this->createUploadedRecording($filename, 'text/plain', 'TestMeeting: 1');
        $client->loginUser($user);

        $client->request('POST', '/room/recordings/remove/' . $filename);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
        $this->assertNull(self::getContainer()->get(UploadedRecordingRepository::class)->findOneBy(['filename' => $filename]));
    }

    public function testRemoveReturnsNotFoundForUnknownFile(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('POST', '/room/recordings/remove/does-not-exist.txt');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testIsValidBearerToken(): void
    {
        static::createClient();
        /** @var RecordingController $controller */
        $controller = self::getContainer()->get(RecordingController::class);
        $method = new \ReflectionMethod(RecordingController::class, 'isValidBearerToken');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'Bearer ' . $this->recordingToken()));
        $this->assertFalse($method->invoke($controller, 'Bearer wrong'));
    }

    public function testGenerateUniqueFileName(): void
    {
        static::createClient();
        /** @var RecordingController $controller */
        $controller = self::getContainer()->get(RecordingController::class);
        $method = new \ReflectionMethod(RecordingController::class, 'generateUniqueFileName');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'movie.mp4');

        $this->assertStringEndsWith('_movie.mp4', $result);
        $this->assertNotSame('_movie.mp4', $result);
    }

    public function testGetFileExtensionFromMimeType(): void
    {
        static::createClient();
        /** @var RecordingController $controller */
        $controller = self::getContainer()->get(RecordingController::class);
        $method = new \ReflectionMethod(RecordingController::class, 'getFileExtensionFromMimeType');
        $method->setAccessible(true);

        $this->assertSame('mp4', $method->invoke($controller, 'video/mp4'));
        $this->assertSame('txt', $method->invoke($controller, 'text/plain'));
        $this->assertSame('bin', $method->invoke($controller, 'application/unknown'));
    }

    private function createUploadedRecording(string $filename, string $type, string $roomName): UploadedRecording
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => $roomName]);
        $recording = new UploadedRecording();
        $recording->setFilename($filename);
        $recording->setType($type);
        $recording->setDisplayName($filename);
        $recording->setRoom($room);
        $recording->setCreatedAt(new \DateTimeImmutable());
        $em->persist($recording);
        $em->flush();

        self::getContainer()->get('gaufrette.recording_fs_filesystem')->write($filename, 'recording content');

        return $recording;
    }
}
