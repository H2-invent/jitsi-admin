<?php

namespace App\Tests\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\UserNewRoomAddService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;

class UserNewRoomAddServiceTest extends KernelTestCase
{
    private function service(): UserNewRoomAddService
    {
        return self::getContainer()->get(UserNewRoomAddService::class);
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

    public function testAddUserToRoomSendsInvitationWithIcsToModerator(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('TestMeeting: 1');
        self::assertSame($user, $room->getModerator());
        $this->expectNoPush();

        $result = $this->service()->addUserToRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Einladung');
        self::assertEmailAttachmentCount($email, 1);
    }

    public function testAddUserToRoomPushesNotificationForParticipant(): void
    {
        self::bootKernel();
        $user = $this->user('test@local4.de');
        $room = $this->room('TestMeeting: 1');
        self::assertNotSame($user, $room->getModerator());
        $this->replaceMercureHub(2);

        $result = $this->service()->addUserToRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailAttachmentCount($email, 1);
    }

    public function testAddUserToPersistantRoomSendsInvitationWithoutIcs(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('This is a fixed room');
        self::assertTrue($room->getPersistantRoom());
        self::assertSame($user, $room->getModerator());
        $this->expectNoPush();

        $result = $this->service()->addUserToPersistantRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Einladung');
        self::assertEmailAttachmentCount($email, 0);
    }

    public function testAddUserScheduleSendsScheduleInvitation(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('Termin finden: 0');
        self::assertTrue($room->getScheduleMeeting());
        self::assertSame($user, $room->getModerator());
        $this->expectNoPush();

        $result = $this->service()->addUserSchedule($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Terminplanung');
        self::assertEmailAttachmentCount($email, 0);
    }

    public function testAddWaitinglistSendsWaitingListNotification(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('TestMeeting: 1');
        $this->expectNoPush();

        $result = $this->service()->addWaitinglist($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Warteliste');
    }

    public function testAddWaitinglistPushesNotificationForParticipant(): void
    {
        self::bootKernel();
        $user = $this->user('test@local4.de');
        $room = $this->room('TestMeeting: 1');
        self::assertNotSame($user, $room->getModerator());
        $this->replaceMercureHub(2);

        $result = $this->service()->addWaitinglist($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Warteliste');
    }
}
