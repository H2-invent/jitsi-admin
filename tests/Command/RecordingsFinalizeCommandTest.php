<?php

namespace App\Tests\Command;

use App\Command\RecordingsFinalizeCommand;
use App\Entity\Recording;
use App\Repository\RecordingRepository;
use App\Repository\RoomsRepository;
use App\Repository\UploadedRecordingRepository;
use App\Service\MailerService;
use App\Service\RecordingService;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\Adapter\Local as GaufretteLocalAdapter;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RecordingsFinalizeCommandTest extends KernelTestCase
{
    private string $tempProjectDir;
    private string $tempRecordingDir;

    protected function setUp(): void
    {
        $this->tempProjectDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'finalize-project-' . uniqid();
        $this->tempRecordingDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'finalize-recording-' . uniqid();
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->tempProjectDir);
        $filesystem->mkdir($this->tempRecordingDir);
    }

    protected function tearDown(): void
    {
        $this->removeTempDirs();
        parent::tearDown();
    }

    private function removeTempDirs(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempProjectDir);
        $filesystem->remove($this->tempRecordingDir);
    }

    private function createRecordingService(): RecordingService
    {
        $container = self::getContainer();

        return new RecordingService(
            $this->tempProjectDir,
            new Filesystem(),
            new GaufretteFilesystem(new GaufretteLocalAdapter($this->tempRecordingDir)),
            $container->get(RecordingRepository::class),
            $container->get(MessageBusInterface::class),
            $container->get(EntityManagerInterface::class),
            $container->get(MailerService::class),
            $container->get(TranslatorInterface::class),
            $container->get(Environment::class),
        );
    }

    public function testFinalizesUploadedChunks(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Running Room']);
        $roomId = $room->getId();

        $uid = 'phpunit-finalize-' . uniqid('', true);
        $recording = new Recording();
        $recording->setUid($uid)
            ->setRoom($room)
            ->setUser($room->getModerator())
            ->setCreatedAt(new \DateTimeImmutable());
        $em->persist($recording);
        $em->flush();

        $chunkDir = $this->tempProjectDir . '/upload_chunks/' . $uid;

        try {
            (new Filesystem())->mkdir($chunkDir);
            file_put_contents($chunkDir . '/chunk_0', 'AAAA');
            file_put_contents($chunkDir . '/chunk_1', 'BBBB');

            $tester = new CommandTester(new RecordingsFinalizeCommand($this->createRecordingService()));
            $tester->execute(['recording_uid' => $uid]);

            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            self::assertStringContainsString('Successfully joined the chunks', $tester->getDisplay());

            $em->clear();
            $uploaded = self::getContainer()->get(UploadedRecordingRepository::class)->findOneBy(['room' => $roomId]);
            self::assertNotNull($uploaded);
            $filename = $uploaded->getFilename();
            self::assertFileExists($this->tempRecordingDir . '/' . $filename);
            self::assertSame('AAAABBBB', file_get_contents($this->tempRecordingDir . '/' . $filename));
        } finally {
            $this->removeTempDirs();
        }
    }

    public function testFailsWhenRecordingUidIsUnknown(): void
    {
        self::bootKernel();

        $tester = new CommandTester(new RecordingsFinalizeCommand($this->createRecordingService()));
        $tester->execute(['recording_uid' => 'does-not-exist-' . uniqid('', true)]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('Could not find recording via uid', $tester->getDisplay());
    }
}
