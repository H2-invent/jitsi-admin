<?php

namespace App\Tests\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\UserServiceEditRoom;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserServiceEditRoomTest extends KernelTestCase
{
    private function service(): UserServiceEditRoom
    {
        return self::getContainer()->get(UserServiceEditRoom::class);
    }

    private function room(string $name): Rooms
    {
        return self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => $name]);
    }

    private function moderator(): User
    {
        return self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
    }

    public function testEditRoomReturnsTrueAndSendsNotification(): void
    {
        self::bootKernel();
        $user = $this->moderator();
        $room = $this->room('TestMeeting: 0');

        $result = $this->service()->editRoom($user, $room);

        self::assertTrue($result);
        self::assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
    }

    public function testEditPersistantRoomReturnsTrueAndSendsNotification(): void
    {
        self::bootKernel();
        $user = $this->moderator();
        $room = $this->room('This is a fixed room');
        self::assertTrue($room->getPersistantRoom());

        $result = $this->service()->editPersistantRoom($user, $room);

        self::assertTrue($result);
        self::assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
    }

    public function testEditRoomScheduleReturnsTrueAndSendsNotification(): void
    {
        self::bootKernel();
        $user = $this->moderator();
        $room = $this->room('Termin finden: 0');
        self::assertTrue($room->getScheduleMeeting());

        $result = $this->service()->editRoomSchedule($user, $room);

        self::assertTrue($result);
        self::assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
    }
}
