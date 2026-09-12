<?php

namespace App\Tests\Service;

use App\Entity\CallerId;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\JoinUrlGeneratorService;
use App\Service\Lobby\DirectSendService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\HubInterface;

class UserServiceTest extends KernelTestCase
{
    private function service(): UserService
    {
        return self::getContainer()->get(UserService::class);
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

    public function testGenerateUrlDelegatesToJoinUrlGenerator(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('TestMeeting: 1');

        $result = $this->service()->generateUrl($room, $user);

        $expected = self::getContainer()->get(JoinUrlGeneratorService::class)->generateUrl($room, $user);
        self::assertSame($expected, $result);
        self::assertStringContainsString(
            base64_encode('uid=' . $room->getUid() . '&email=' . $user->getEmail()),
            $result
        );
    }

    public function testAddUserToRegularRoomSendsInvitationAndCreatesCallerId(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('TestMeeting: 1');
        self::assertNotTrue($room->getScheduleMeeting());
        self::assertNotTrue($room->getPersistantRoom());
        $this->expectNoPush();

        $result = $this->service()->addUser($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Einladung');
        self::assertEmailAttachmentCount($email, 1);
        $callerId = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(CallerId::class)
            ->findOneBy(['room' => $room, 'user' => $user]);
        self::assertNotNull($callerId);
    }

    public function testAddUserGeneratesAndPersistsUid(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $user->setUid(null);
        $room = $this->room('TestMeeting: 1');
        $this->expectNoPush();

        $result = $this->service()->addUser($user, $room);

        self::assertTrue($result);
        self::assertNotNull($user->getUid());
        self::assertSame(32, strlen($user->getUid()));
        $stored = self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->find($user->getId());
        self::assertSame($user->getUid(), $stored->getUid());
        $this->assertEmailCount(1);
    }

    public function testAddUserToPersistantRoomSendsInvitationWithoutIcs(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('This is a fixed room');
        self::assertTrue($room->getPersistantRoom());
        $this->expectNoPush();

        $result = $this->service()->addUser($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Einladung');
        self::assertEmailAttachmentCount($email, 0);
    }

    public function testAddUserToScheduleRoomSendsScheduleInvitation(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('Termin finden: 0');
        self::assertTrue($room->getScheduleMeeting());
        $this->expectNoPush();

        $result = $this->service()->addUser($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Terminplanung');
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

    public function testEditRoomDelegatesToRegularEditRoom(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('TestMeeting: 1');
        $this->expectNoPush();

        $result = $this->service()->editRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'bearbeitet');
        self::assertEmailAttachmentCount($email, 1);
    }

    public function testEditRoomDelegatesToPersistantEditRoom(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('This is a fixed room');
        $this->expectNoPush();

        $result = $this->service()->editRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'bearbeitet');
        self::assertEmailAttachmentCount($email, 0);
    }

    public function testEditRoomDelegatesToScheduleEditRoom(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('Termin finden: 0');
        $this->expectNoPush();

        $result = $this->service()->editRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Terminplanung');
    }

    public function testRemoveRoomSchedulesRemovalForScheduleMeeting(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('Termin finden: 0');
        self::assertTrue($room->getScheduleMeeting());
        $this->expectNoPush();

        $result = $this->service()->removeRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Terminplanung');
    }

    public function testRemoveRoomRemovesPersistantRoom(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('This is a fixed room');
        self::assertTrue($room->getPersistantRoom());
        $this->expectNoPush();

        $result = $this->service()->removeRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailAttachmentCount($email, 0);
    }

    public function testRemoveRoomRemovesFutureRoom(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('Room Tomorrow');
        $room->setHostUrl('https://meet.jit.si');
        self::assertGreaterThan(new \DateTime(), $room->getEnddate());
        self::assertNotTrue($room->getScheduleMeeting());
        self::assertNotTrue($room->getPersistantRoom());
        $this->expectNoPush();

        $result = $this->service()->removeRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'abgesagt');
        self::assertEmailAttachmentCount($email, 1);
    }

    public function testRemoveRoomDoesNothingForPastRoom(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('Room Yesterday');
        self::assertNotTrue($room->getScheduleMeeting());
        self::assertNotTrue($room->getPersistantRoom());
        self::assertLessThan(new \DateTime(), $room->getEnddate());
        $this->expectNoPush();

        $result = $this->service()->removeRoom($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(0);
    }

    public function testNotifyUserSendsCronMailAndPush(): void
    {
        self::bootKernel();
        $user = $this->user('test@local.de');
        $room = $this->room('TestMeeting: 1');
        $this->replaceMercureHub(2);

        $result = $this->service()->notifyUser($user, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailSubjectContains($email, 'Erinnerung');
    }
}
