<?php

namespace App\Tests\MessageHandler;

use App\Entity\Recording;
use App\Message\RecordingUploadedMessage;
use App\MessageHandler\RecordingUploadedMessageHandler;
use App\Repository\RoomsRepository;
use App\Service\RecordingService;
use App\Service\Result\Error\RecordingFinalizeError;
use App\Service\Result\ServiceResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class RecordingUploadedMessageHandlerTest extends KernelTestCase
{
    public function testUnknownRecordingThrowsUnrecoverableException(): void
    {
        self::bootKernel();
        $handler = new RecordingUploadedMessageHandler(self::getContainer()->get(RecordingService::class));

        $this->expectException(UnrecoverableMessageHandlingException::class);

        $handler(new RecordingUploadedMessage('missing-' . uniqid()));
    }

    public function testRecordingWithoutChunksThrowsUnrecoverableException(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $uid = 'no-chunks-' . uniqid();

        $recording = (new Recording())
            ->setUid($uid)
            ->setRoom($room)
            ->setCreatedAt(new \DateTimeImmutable());
        $entityManager->persist($recording);
        $entityManager->flush();

        $tempDir = self::getContainer()->getParameter('kernel.project_dir') . '/upload_chunks/' . $uid;
        @mkdir($tempDir, 0777, true);

        try {
            $handler = new RecordingUploadedMessageHandler(self::getContainer()->get(RecordingService::class));

            $this->expectException(UnrecoverableMessageHandlingException::class);

            $handler(new RecordingUploadedMessage($uid));
        } finally {
            if (is_dir($tempDir)) {
                @rmdir($tempDir);
            }
        }
    }

    public function testWriteFailureThrowsRecoverableException(): void
    {
        $recordingService = $this->createMock(RecordingService::class);
        $recordingService->method('finalizeUpload')
            ->willReturn(ServiceResult::failure(RecordingFinalizeError::COULD_NOT_WRITE_FINAL_FILE));

        $handler = new RecordingUploadedMessageHandler($recordingService);

        $this->expectException(RecoverableMessageHandlingException::class);

        $handler(new RecordingUploadedMessage('any'));
    }

    public function testSuccessfulFinalizationDoesNotThrow(): void
    {
        $recordingService = $this->createMock(RecordingService::class);
        $recordingService->method('finalizeUpload')->willReturn(ServiceResult::success());

        $handler = new RecordingUploadedMessageHandler($recordingService);
        $handler(new RecordingUploadedMessage('any'));

        $this->addToAssertionCount(1);
    }
}
