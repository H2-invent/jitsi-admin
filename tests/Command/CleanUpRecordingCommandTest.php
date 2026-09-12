<?php

namespace App\Tests\Command;

use App\Command\CleanUpRecordingCommand;
use App\Entity\UploadedRecording;
use App\Repository\RoomsRepository;
use App\Repository\UploadedRecordingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\Adapter\Local as GaufretteLocalAdapter;
use Gaufrette\Filesystem as GaufretteFilesystem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class CleanUpRecordingCommandTest extends KernelTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cleanup-recording-' . uniqid();
        (new Filesystem())->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
        parent::tearDown();
    }

    private function createCommand(): CleanUpRecordingCommand
    {
        return new CleanUpRecordingCommand(
            new GaufretteFilesystem(new GaufretteLocalAdapter($this->tempDir)),
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(UploadedRecordingRepository::class)
        );
    }

    public function testDeletesRecordingsOlderThanCutoff(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Running Room']);

        $filename = 'phpunit-recording-' . uniqid('', true) . '.mp4';
        file_put_contents($this->tempDir . '/' . $filename, 'recording-content');

        $recording = new UploadedRecording();
        $recording->setFilename($filename)
            ->setRoom($room)
            ->setCreatedAt(new \DateTimeImmutable('-30 days'))
            ->setType('video/mp4')
            ->setDisplayName('old recording.mp4');
        $em->persist($recording);
        $em->flush();
        $id = $recording->getId();

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $tester->execute(['days' => 10]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Deleted recording: ' . $filename, $tester->getDisplay());
        self::assertStringContainsString('Cleanup complete.', $tester->getDisplay());
        self::assertFileDoesNotExist($this->tempDir . '/' . $filename);

        $em->clear();
        self::assertNull(self::getContainer()->get(UploadedRecordingRepository::class)->find($id));
    }

    public function testKeepsRecordingsNewerThanCutoff(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Running Room']);

        $filename = 'phpunit-recording-' . uniqid('', true) . '.mp4';
        file_put_contents($this->tempDir . '/' . $filename, 'recording-content');

        $recording = new UploadedRecording();
        $recording->setFilename($filename)
            ->setRoom($room)
            ->setCreatedAt(new \DateTimeImmutable('-1 day'))
            ->setType('video/mp4')
            ->setDisplayName('new recording.mp4');
        $em->persist($recording);
        $em->flush();
        $id = $recording->getId();

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $tester->execute(['days' => 10]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('No recordings to delete.', $tester->getDisplay());
        self::assertFileExists($this->tempDir . '/' . $filename);

        $em->clear();
        self::assertNotNull(self::getContainer()->get(UploadedRecordingRepository::class)->find($id));
    }

    public function testFailsForNonPositiveDays(): void
    {
        self::bootKernel();

        $command = $this->createCommand();
        $tester = new CommandTester($command);
        $tester->execute(['days' => 0]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('The number of days must be greater than 0.', $tester->getDisplay());
    }
}
