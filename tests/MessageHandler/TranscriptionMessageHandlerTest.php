<?php

namespace App\Tests\MessageHandler;

use App\Entity\UploadedRecording;
use App\Message\TranscriptionMessage;
use App\MessageHandler\TranscriptionMessageHandler;
use App\Repository\RoomsRepository;
use App\Repository\UploadedRecordingRepository;
use App\Service\Transcription\TranscriptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TranscriptionMessageHandlerTest extends KernelTestCase
{
    public function testInvokeLoadsTheRecordingAndStartsTranscription(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);

        $recording = (new UploadedRecording())
            ->setFilename('file.mp4')
            ->setDisplayName('file.mp4')
            ->setType('video/mp4')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setRoom($room);
        $entityManager->persist($recording);
        $entityManager->flush();

        // Transcription talks to an external provider (OpenAI/Mistral), so it is replaced by a test double.
        $transcriptionService = $this->createMock(TranscriptionService::class);
        $transcriptionService->expects($this->once())
            ->method('transcribe')
            ->with($recording);

        $handler = new TranscriptionMessageHandler(
            $transcriptionService,
            self::getContainer()->get(UploadedRecordingRepository::class),
        );

        $handler(new TranscriptionMessage($recording->getId()));
    }
}
