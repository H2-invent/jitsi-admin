<?php

namespace App\Tests\Repository;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Repository\RoomsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomsRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private RoomsRepository $roomsRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->roomsRepository = self::getContainer()->get(RoomsRepository::class);
    }

    public function testCountUsersForServer(): void
    {
        $server = $this->createServer();
        $room1 = $this->createRoom($server, '2026-01-15 10:00:00', false);
        $room2 = $this->createRoom($server, '2026-01-16 10:00:00', false);
        $room1->addUser($this->createUser());
        $room1->addUser($this->createUser());
        $room2->addUser($this->createUser());
        $this->entityManager->flush();

        $count = $this->roomsRepository->countUsersForServer($server);

        $this->assertSame(3, $count);
    }

    public function testCountUsersForServerCountsUserPerRoomMultipleTimes(): void
    {
        $server = $this->createServer();
        $room1 = $this->createRoom($server, '2026-01-15 10:00:00', false);
        $room2 = $this->createRoom($server, '2026-01-16 10:00:00', false);
        $user = $this->createUser();
        $room1->addUser($user);
        $room2->addUser($user);
        $this->entityManager->flush();

        $count = $this->roomsRepository->countUsersForServer($server);

        $this->assertSame(2, $count);
    }

    public function testCountUsersForServerReturnsZeroWithoutRooms(): void
    {
        $server = $this->createServer();
        $this->entityManager->flush();

        $count = $this->roomsRepository->countUsersForServer($server);

        $this->assertSame(0, $count);
    }

    public function testCountUsersForServerDoesNotCountRoomsOfOtherServers(): void
    {
        $server = $this->createServer();
        $otherServer = $this->createServer();
        $room = $this->createRoom($server, '2026-01-15 10:00:00', false);
        $room->addUser($this->createUser());
        $otherRoom = $this->createRoom($otherServer, '2026-01-15 10:00:00', false);
        $otherRoom->addUser($this->createUser());
        $otherRoom->addUser($this->createUser());
        $this->entityManager->flush();

        $this->assertSame(1, $this->roomsRepository->countUsersForServer($server));
        $this->assertSame(2, $this->roomsRepository->countUsersForServer($otherServer));
    }

    public function testFindRoomsWithUserCountForServer(): void
    {
        $server = $this->createServer();
        $room1 = $this->createRoom($server, '2026-01-15 10:00:00', false);
        $room2 = $this->createRoom($server, '2026-01-15 14:00:00', null);
        $room3 = $this->createRoom($server, '2026-01-16 10:00:00', false);
        $emptyRoom = $this->createRoom($server, '2026-01-17 10:00:00', false);

        $scheduledRoom = $this->createRoom($server, '2026-01-15 10:00:00', true);
        $roomWithoutStart = $this->createRoom($server, null, false);
        $prototypeRoom = $this->createRoom($server, '2026-01-15 10:00:00', false);

        $room1->addUser($this->createUser());
        $room1->addUser($this->createUser());
        $room2->addUser($this->createUser());
        $room2->addUser($this->createUser());
        $room2->addUser($this->createUser());
        $room3->addUser($this->createUser());
        $scheduledRoom->addUser($this->createUser());
        $scheduledRoom->addUser($this->createUser());
        $scheduledRoom->addUser($this->createUser());
        $scheduledRoom->addUser($this->createUser());
        $roomWithoutStart->addUser($this->createUser());
        $roomWithoutStart->addUser($this->createUser());
        $prototypeRoom->addUser($this->createUser());
        $prototypeRoom->addUser($this->createUser());

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setStartDate(new \DateTime('2026-01-01 10:00:00'));
        $repeat->setPrototyp($prototypeRoom);
        $this->entityManager->persist($repeat);

        $this->entityManager->flush();

        $result = $this->roomsRepository->findRoomsWithUserCountForServer($server);

        $byRoomId = [];
        foreach ($result as $row) {
            $this->assertArrayHasKey('roomId', $row);
            $this->assertArrayHasKey('start', $row);
            $this->assertArrayHasKey('participantCount', $row);
            $this->assertInstanceOf(\DateTimeInterface::class, $row['start']);
            $byRoomId[$row['roomId']] = [
                'participantCount' => (int)$row['participantCount'],
                'start' => $row['start'],
            ];
        }

        $this->assertCount(4, $result);
        $this->assertArrayNotHasKey($scheduledRoom->getId(), $byRoomId);
        $this->assertArrayNotHasKey($roomWithoutStart->getId(), $byRoomId);
        $this->assertArrayNotHasKey($prototypeRoom->getId(), $byRoomId);
        $this->assertSame(2, $byRoomId[$room1->getId()]['participantCount']);
        $this->assertSame(3, $byRoomId[$room2->getId()]['participantCount']);
        $this->assertSame(1, $byRoomId[$room3->getId()]['participantCount']);
        $this->assertSame(0, $byRoomId[$emptyRoom->getId()]['participantCount']);
        $this->assertSame('20260115', $byRoomId[$room1->getId()]['start']->format('Ymd'));
        $this->assertSame('20260116', $byRoomId[$room3->getId()]['start']->format('Ymd'));
    }

    public function testFindRoomsWithUserCountForServerReturnsEmptyArrayForServerWithoutRooms(): void
    {
        $server = $this->createServer();
        $this->entityManager->flush();

        $this->assertSame([], $this->roomsRepository->findRoomsWithUserCountForServer($server));
    }

    private function createServer(): Server
    {
        $server = new Server();
        $server->setUrl(uniqid('url-', true) . '.test.de')
            ->setSlug(uniqid('slug-', true))
            ->setServerName('test-server')
            ->setJwtModeratorPosition(0);
        $this->entityManager->persist($server);
        return $server;
    }

    private function createRoom(Server $server, ?string $start, ?bool $scheduleMeeting): Rooms
    {
        $room = new Rooms();
        $room->setName('test-room')
            ->setUid(uniqid('uid-', true))
            ->setUidReal(uniqid('uidReal-', true))
            ->setDuration(60)
            ->setSequence(0)
            ->setScheduleMeeting($scheduleMeeting);
        if ($start !== null) {
            $room->setStart(new \DateTime($start));
        }
        $room->setServer($server);
        $this->entityManager->persist($room);
        return $room;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail(uniqid('mail-', true) . '@test.de')
            ->setUuid(uniqid('uuid-', true))
            ->setPassword('test-password');
        $this->entityManager->persist($user);
        return $user;
    }
}
