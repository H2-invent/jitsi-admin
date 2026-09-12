<?php

namespace App\Tests\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\UserServiceRemoveRoom;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;

class UserServiceRemoveRoomTest extends KernelTestCase
{
    private function service(): UserServiceRemoveRoom
    {
        return self::getContainer()->get(UserServiceRemoveRoom::class);
    }

    private function user(string $email): User
    {
        return self::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
    }

    private function room(string $name): Rooms
    {
        return self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => $name]);
    }

    private function replaceMercureHub(int $expectedPublications): HubInterface
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->exactly($expectedPublications))->method('publish')->willReturn('id');
        self::getContainer()->get(DirectSendService::class)->setMercurePublisher($hub);

        return $hub;
    }

    private function expectNoPush(): HubInterface
    {
        $hub = $this->createMock(HubInterface::class);
        $hub->expects($this->never())->method('publish');
        self::getContainer()->get(DirectSendService::class)->setMercurePublisher($hub);

        return $hub;
    }

    public function testRemoveRoomSendsCancellationWithIcsToModerator(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('TestMeeting: 1');
        self::assertSame($user, $room->getModerator());
        $this->expectNoPush();

        $result = $this->service()->removeRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'abgesagt');
        self::assertEmailAttachmentCount($email, 1);
    }

    public function testRemoveRoomPushesCancellationForParticipant(): void
    {
        self::bootKernel();
        $user = $this->user('test@local4.de');
        $room = $this->room('TestMeeting: 1');
        self::assertNotSame($user, $room->getModerator());
        $this->replaceMercureHub(2);

        $result = $this->service()->removeRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailAttachmentCount($email, 1);
    }

    public function testRemovePersistantRoomForOrganizerDoesNotPush(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('This is a fixed room');
        self::assertTrue($room->getPersistantRoom());
        self::assertSame($user, $room->getModerator());
        self::assertSame($user, $room->getCreator());
        $this->expectNoPush();

        $result = $this->service()->removePersistantRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailAttachmentCount($email, 0);
    }

    public function testRemovePersistantRoomPushesForNonOrganizer(): void
    {
        self::bootKernel();
        $user = $this->user('test@local4.de');
        $room = $this->room('This is a fixed room');
        self::assertNotSame($user, $room->getModerator());
        self::assertNotSame($user, $room->getCreator());
        $this->replaceMercureHub(2);

        $result = $this->service()->removePersistantRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailAttachmentCount($email, 0);
    }

    public function testRemoveRoomSchedulingSendsCancellation(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('Termin finden: 0');
        self::assertTrue($room->getScheduleMeeting());
        $this->expectNoPush();

        $result = $this->service()->removeRoomScheduling($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Terminplanung');
    }
}
