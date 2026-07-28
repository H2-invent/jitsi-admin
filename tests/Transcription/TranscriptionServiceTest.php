<?php

namespace App\Tests\Transcription;

use App\Repository\RoomsRepository;
use App\Repository\TranscriptionRepository;
use App\Service\TranscriptionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TranscriptionServiceTest extends KernelTestCase
{
    public function testAddNewTranscriptionCreatesAndPersistsTranscription(): void
    {
        self::bootKernel();

        $transcriptionService = self::getContainer()->get(TranscriptionService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $transcriptionRepo = self::getContainer()->get(TranscriptionRepository::class);

        $room = $roomRepo->findOneBy([]);
        $this->assertNotNull($room);
        $this->assertNotNull($room->getModerator());

        $text = 'This is a test transcription text for the meeting.';

        $transcription = $transcriptionService->addNewTranscription($room, $text);

        $this->assertNotNull($transcription);
        $this->assertSame($room, $transcription->getRoom());
        $this->assertSame($text, $transcription->getText());

        // Verify it was persisted
        $persistedTranscription = $transcriptionRepo->find($transcription->getId());
        $this->assertNotNull($persistedTranscription);
        $this->assertSame($text, $persistedTranscription->getText());

        // Verify email was sent to moderator
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $room->getModerator()->getEmail());
    }

    public function testAddNewTranscriptionWithoutModeratorDoesNotSendEmail(): void
    {
        self::bootKernel();

        $transcriptionService = self::getContainer()->get(TranscriptionService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $transcriptionRepo = self::getContainer()->get(TranscriptionRepository::class);

        // Find a room and remove its moderator for this test
        $room = $roomRepo->findOneBy([]);
        $this->assertNotNull($room);

        $originalModerator = $room->getModerator();
        $room->setModerator(null);

        $text = 'Transcription for room without moderator';

        $transcription = $transcriptionService->addNewTranscription($room, $text);

        $this->assertNotNull($transcription);
        $this->assertSame($room, $transcription->getRoom());
        $this->assertSame($text, $transcription->getText());

        // Verify no email was sent
        self::assertEmailCount(0);

        // Restore moderator
        $room->setModerator($originalModerator);
    }

    public function testAddNewTranscriptionEmailContainsRoomName(): void
    {
        self::bootKernel();

        $transcriptionService = self::getContainer()->get(TranscriptionService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);

        $room = $roomRepo->findOneBy([]);
        $this->assertNotNull($room);
        $this->assertNotNull($room->getModerator());

        $text = 'Meeting transcription content';

        $transcriptionService->addNewTranscription($room, $text);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();

        // Email should contain the room name
        self::assertEmailHtmlBodyContains($email, $room->getName());
    }
}
