<?php

namespace App\Tests\Repository;

use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Repository\RoomStatusParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomStatusParticipantRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private RoomStatusParticipantRepository $roomStatusParticipantRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->roomStatusParticipantRepository = self::getContainer()->get(RoomStatusParticipantRepository::class);
    }

    public function testFindParticipantsByServer(): void
    {
        $server = $this->createServer();
        $room = $this->createRoom($server);
        $roomStatus = $this->createRoomStatus($room);

        $this->createParticipant($roomStatus, '2026-01-10 10:00:00');
        $this->createParticipant($roomStatus, '2026-01-15 10:00:00');
        $this->createParticipant($roomStatus, '2026-01-31 23:00:00');
        $this->createParticipant($roomStatus, '2026-02-05 10:00:00');
        $this->entityManager->flush();

        $result = $this->roomStatusParticipantRepository->findParticipantsByServer(
            $server,
            new \DateTime('2026-01-01 00:00:00'),
            new \DateTime('2026-01-31 23:59:59')
        );

        $this->assertCount(3, $result);
        $enteredDates = array_map(static fn(RoomStatusParticipant $p) => $p->getEnteredRoomAt()->format('Ymd'), $result);
        $this->assertContains('20260110', $enteredDates);
        $this->assertContains('20260115', $enteredDates);
        $this->assertContains('20260131', $enteredDates);
    }

    public function testFindParticipantsByServerReturnsEmptyArrayForServerWithoutParticipants(): void
    {
        $server = $this->createServer();
        $this->entityManager->flush();

        $result = $this->roomStatusParticipantRepository->findParticipantsByServer(
            $server,
            new \DateTime('2026-01-01 00:00:00'),
            new \DateTime('2026-01-31 23:59:59')
        );

        $this->assertSame([], $result);
    }

    public function testFindParticipantsByServerDoesNotIncludeParticipantsOfOtherServers(): void
    {
        $server = $this->createServer();
        $otherServer = $this->createServer();

        $room = $this->createRoom($server);
        $this->createParticipant($this->createRoomStatus($room), '2026-01-15 10:00:00');

        $otherRoom = $this->createRoom($otherServer);
        $this->createParticipant($this->createRoomStatus($otherRoom), '2026-01-15 10:00:00');
        $this->entityManager->flush();

        $result = $this->roomStatusParticipantRepository->findParticipantsByServer(
            $server,
            new \DateTime('2026-01-01 00:00:00'),
            new \DateTime('2026-01-31 23:59:59')
        );

        $this->assertCount(1, $result);
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

    private function createRoom(Server $server): Rooms
    {
        $room = new Rooms();
        $room->setName('test-room')
            ->setUid(uniqid('uid-', true))
            ->setUidReal(uniqid('uidReal-', true))
            ->setDuration(60)
            ->setSequence(0)
            ->setScheduleMeeting(false);
        $room->setStart(new \DateTime('2026-01-15 10:00:00'));
        $room->setServer($server);
        $this->entityManager->persist($room);
        return $room;
    }

    private function createRoomStatus(Rooms $room): RoomStatus
    {
        $roomStatus = new RoomStatus();
        $roomStatus->setCreated(true)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setJitsiRoomId(uniqid('jitsi-', true))
            ->setRoom($room);
        $this->entityManager->persist($roomStatus);
        return $roomStatus;
    }

    private function createParticipant(RoomStatus $roomStatus, string $enteredAt): RoomStatusParticipant
    {
        $participant = new RoomStatusParticipant();
        $participant->setInRoom(true)
            ->setEnteredRoomAt(new \DateTime($enteredAt))
            ->setRoomStatus($roomStatus)
            ->setParticipantId(uniqid('part-', true))
            ->setParticipantName('test-participant');
        $this->entityManager->persist($participant);
        return $participant;
    }
}
