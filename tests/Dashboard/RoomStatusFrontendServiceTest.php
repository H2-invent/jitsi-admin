<?php

namespace App\Tests\Dashboard;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomStatusFrontendServiceTest extends KernelTestCase
{
    public function testGetRoomCreatedStatusMapWithActiveRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $runningRoom = $roomRepo->findOneBy(['name' => 'Running Room']);
        $this->assertNotNull($runningRoom);

        $result = $service->getRoomCreatedStatusMap([$runningRoom->getId()]);

        $this->assertArrayHasKey($runningRoom->getId(), $result);
        $this->assertTrue($result[$runningRoom->getId()]);
    }

    public function testGetRoomCreatedStatusMapWithNonExistentRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $result = $service->getRoomCreatedStatusMap([-1]);

        $this->assertEmpty($result);
    }

    public function testGetRoomCreatedStatusMapWithRoomWithoutStatus(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $this->assertNotNull($room);

        $result = $service->getRoomCreatedStatusMap([$room->getId()]);

        $this->assertEmpty($result);
    }

    public function testGetRoomCreatedStatusMapWithEmptyArray(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $result = $service->getRoomCreatedStatusMap([]);

        $this->assertEmpty($result);
    }

    public function testGetRoomOccupantsMapWithActiveRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $runningRoom = $roomRepo->findOneBy(['name' => 'Running Room']);
        $this->assertNotNull($runningRoom);

        $result = $service->getRoomOccupantsMap([$runningRoom->getId()]);

        $this->assertArrayHasKey($runningRoom->getId(), $result);
        $this->assertContains('in der Konferenz', $result[$runningRoom->getId()]);
    }

    public function testGetRoomOccupantsMapWithRoomWithoutStatus(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $this->assertNotNull($room);

        $result = $service->getRoomOccupantsMap([$room->getId()]);

        $this->assertEmpty($result);
    }

    public function testGetRoomOccupantsMapWithEmptyArray(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $result = $service->getRoomOccupantsMap([]);

        $this->assertEmpty($result);
    }

    public function testGetRoomClosedStatusMapWithActiveRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $runningRoom = $roomRepo->findOneBy(['name' => 'Running Room']);
        $this->assertNotNull($runningRoom);

        $result = $service->getRoomClosedStatusMap([$runningRoom->getId()]);

        $this->assertArrayHasKey($runningRoom->getId(), $result);
        $this->assertFalse($result[$runningRoom->getId()]);
    }

    public function testGetRoomClosedStatusMapWithDestroyedRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $room = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $this->assertNotNull($room);

        $roomStatus = new RoomStatus();
        $roomStatus->setCreated(true)
            ->setRoomCreatedAt((new \DateTime())->modify('-3 hours'))
            ->setRoom($room)
            ->setJitsiRoomId('testclosed@test.de')
            ->setDestroyed(true)
            ->setDestroyedAt((new \DateTime())->modify('-1 hour'))
            ->setUpdatedAt(new \DateTime())
            ->setCreatedAt(new \DateTime());
        $em->persist($roomStatus);
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertTrue($result[$room->getId()]);
    }

    public function testGetRoomClosedStatusMapWithRoomWithoutStatus(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $this->assertNotNull($room);

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayNotHasKey($room->getId(), $result);
    }

    public function testGetRoomClosedStatusMapWithEmptyArray(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $result = $service->getRoomClosedStatusMap([]);

        $this->assertEmpty($result);
    }

    public function testIsRoomCreatedMatchesBatchMap(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $runningRoom = $roomRepo->findOneBy(['name' => 'Running Room']);

        $single = $service->isRoomCreated($runningRoom);
        $batch = $service->getRoomCreatedStatusMap([$runningRoom->getId()]);

        $this->assertEquals($single, $batch[$runningRoom->getId()] ?? false);
    }

    public function testNumberOfOccupantsMatchesBatchMap(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $runningRoom = $roomRepo->findOneBy(['name' => 'Running Room']);

        $singleResult = $service->numberOfOccupants($runningRoom);
        $batchResult = $service->getRoomOccupantsMap([$runningRoom->getId()]);

        $singleNames = array_map(fn($p) => $p->getParticipantName(), $singleResult);

        $this->assertEqualsCanonicalizing($singleNames, $batchResult[$runningRoom->getId()] ?? []);
    }

    public function testIsRoomClosedRejectsPartiallyDestroyed(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $this->assertNotNull($room);

        $roomStatus1 = new RoomStatus();
        $roomStatus1->setCreated(true)
            ->setRoomCreatedAt((new \DateTime())->modify('-3 hours'))
            ->setRoom($room)
            ->setJitsiRoomId('partial1@test.de')
            ->setDestroyed(true)
            ->setDestroyedAt((new \DateTime())->modify('-2 hours'))
            ->setUpdatedAt(new \DateTime())
            ->setCreatedAt(new \DateTime());
        $em->persist($roomStatus1);

        $roomStatus2 = new RoomStatus();
        $roomStatus2->setCreated(true)
            ->setRoomCreatedAt((new \DateTime())->modify('-1 hour'))
            ->setRoom($room)
            ->setJitsiRoomId('partial2@test.de')
            ->setDestroyed(null)
            ->setUpdatedAt(new \DateTime())
            ->setCreatedAt(new \DateTime());
        $em->persist($roomStatus2);
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertFalse($result[$room->getId()]);
    }

    public function testMultipleRoomsInBatch(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $runningRoom = $roomRepo->findOneBy(['name' => 'Running Room']);
        $yesterdayRoom = $roomRepo->findOneBy(['name' => 'Room Yesterday']);
        $tomorrowRoom = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);

        $roomIds = [$runningRoom->getId(), $yesterdayRoom->getId(), $tomorrowRoom->getId()];

        $createdMap = $service->getRoomCreatedStatusMap($roomIds);
        $occupantsMap = $service->getRoomOccupantsMap($roomIds);
        $closedMap = $service->getRoomClosedStatusMap($roomIds);

        $this->assertArrayHasKey($runningRoom->getId(), $createdMap);
        $this->assertArrayHasKey($runningRoom->getId(), $occupantsMap);
        $this->assertArrayHasKey($runningRoom->getId(), $closedMap);

        $this->assertArrayNotHasKey($yesterdayRoom->getId(), $createdMap);
        $this->assertArrayNotHasKey($yesterdayRoom->getId(), $occupantsMap);
        $this->assertArrayNotHasKey($yesterdayRoom->getId(), $closedMap);
    }

    private function createRoom(EntityManagerInterface $em, string $name, ?\DateTimeInterface $start = null): Rooms
    {
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => $name]);
        if ($room === null) {
            $userRepo = $this->getContainer()->get(UserRepository::class);
            $user = $userRepo->findOneBy(['email' => 'test@local.de']);
            $server = $user->getServers()->toArray()[0];
            $room = new Rooms();
            $room->setName($name)
                ->setTimeZone('Europe/Berlin')
                ->setModerator($user)
                ->setCreator($user)
                ->setDuration(60)
                ->setSequence(0)
                ->setUid(md5(uniqid($name, true)))
                ->setUidReal(md5(uniqid($name, true)))
                ->setServer($server);
            if ($start !== null) {
                $room->setStart($start);
                $room->setEnddate((clone $start)->modify('+60min'));
            }
            $em->persist($room);
            $em->flush();
        }
        return $room;
    }

    private function createStatus(EntityManagerInterface $em, Rooms $room, string $jitsiId, ?bool $destroyed, ?\DateTimeInterface $destroyedAt = null, ?\DateTimeInterface $roomCreatedAt = null): RoomStatus
    {
        $status = new RoomStatus();
        $status->setCreated(true)
            ->setRoom($room)
            ->setJitsiRoomId($jitsiId)
            ->setDestroyed($destroyed)
            ->setDestroyedAt($destroyedAt)
            ->setRoomCreatedAt($roomCreatedAt ?? new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setCreatedAt(new \DateTime());
        $em->persist($status);
        return $status;
    }

    public function testGetRoomClosedStatusMapActiveStatusReturnsFalse(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapActiveRoom', new \DateTime('-2 hours'));

        $this->createStatus($em, $room, 'active-only@test.de', null);
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertFalse($result[$room->getId()], 'Room with an active (non-destroyed) status must not be closed');
    }

    public function testGetRoomClosedStatusMapDestroyedAfterStartReturnsTrue(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapDestroyedAfter', new \DateTime('-3 hours'));

        $this->createStatus($em, $room, 'destroyed-after@test.de', true, new \DateTime('-1 hour'), new \DateTime('-3 hours'));
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertTrue($result[$room->getId()], 'Room destroyed after it started must be closed');
    }

    public function testGetRoomClosedStatusMapDestroyedBeforeStartReturnsFalse(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapDestroyedBefore', new \DateTime('+2 hours'));

        $this->createStatus($em, $room, 'destroyed-before@test.de', true, new \DateTime('-1 hour'), new \DateTime('-1 hour'));
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertFalse($result[$room->getId()], 'Room destroyed BEFORE it started must not be considered closed');
    }

    public function testGetRoomClosedStatusMapNoStatusAbsent(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapNoStatus', new \DateTime('-2 hours'));

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayNotHasKey($room->getId(), $result, 'Room with no status must be absent from the closed map');
    }

    public function testGetRoomClosedStatusMapMixedActiveAndDestroyedReturnsFalse(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapMixed', new \DateTime('-4 hours'));

        $this->createStatus($em, $room, 'mixed-1@test.de', true, new \DateTime('-2 hours'), new \DateTime('-4 hours'));
        $this->createStatus($em, $room, 'mixed-2@test.de', null, null, new \DateTime('-1 hour'));
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertFalse($result[$room->getId()], 'Room with one active and one destroyed status must not be closed');
    }

    public function testGetRoomClosedStatusMapMultipleDestroyedAfterStartReturnsTrue(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapMultipleDestroyed', new \DateTime('-6 hours'));

        $this->createStatus($em, $room, 'multi-1@test.de', true, new \DateTime('-5 hours'), new \DateTime('-6 hours'));
        $this->createStatus($em, $room, 'multi-2@test.de', true, new \DateTime('-1 hour'), new \DateTime('-2 hours'));
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertTrue($result[$room->getId()], 'Room with all destroyed statuses (latest after start) must be closed');
    }

    public function testGetRoomClosedStatusMapDestroyedAtNullReturnsFalse(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapDestroyedAtNull', new \DateTime('-2 hours'));

        $this->createStatus($em, $room, 'nn-destroyed@test.de', true, null, new \DateTime('-2 hours'));
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayNotHasKey($room->getId(), $result, 'Room with destroyed=true but no destroyedAt must be absent');
    }

    public function testGetRoomClosedStatusMapBatchOfDifferentStates(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $activeRoom = $this->createRoom($em, 'BatchActiveRoom', new \DateTime('-1 hour'));
        $this->createStatus($em, $activeRoom, 'batch-active@test.de', null);
        $em->flush();

        $closedRoom = $this->createRoom($em, 'BatchClosedRoom', new \DateTime('-3 hours'));
        $this->createStatus($em, $closedRoom, 'batch-closed@test.de', true, new \DateTime('-1 hour'), new \DateTime('-3 hours'));
        $em->flush();

        $emptyRoom = $this->createRoom($em, 'BatchEmptyRoom', new \DateTime('-2 hours'));

        $result = $service->getRoomClosedStatusMap([$activeRoom->getId(), $closedRoom->getId(), $emptyRoom->getId()]);

        $this->assertArrayHasKey($activeRoom->getId(), $result);
        $this->assertFalse($result[$activeRoom->getId()]);
        $this->assertArrayHasKey($closedRoom->getId(), $result);
        $this->assertTrue($result[$closedRoom->getId()]);
        $this->assertArrayNotHasKey($emptyRoom->getId(), $result);
    }

    public function testGetRoomClosedStatusMapNullDestroyedTreatedAsActive(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'ClosedMapNullDestroyed', new \DateTime('-2 hours'));

        // destroyed explicitly NULL (not true) => still active
        $this->createStatus($em, $room, 'null-destroyed@test.de', null, null, new \DateTime('-2 hours'));
        $em->flush();

        $result = $service->getRoomClosedStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertFalse($result[$room->getId()], 'destroyed=NULL means active, room must not be closed');
    }

    public function testGetRoomHasStatusMapWithActiveRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'HasStatusActiveRoom', new \DateTime('-1 hour'));

        $this->createStatus($em, $room, 'has-active@test.de', null);
        $em->flush();

        $result = $service->getRoomHasStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertTrue($result[$room->getId()]);
    }

    public function testGetRoomHasStatusMapWithDestroyedRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'HasStatusDestroyedRoom', new \DateTime('-3 hours'));

        $this->createStatus($em, $room, 'has-destroyed@test.de', true, new \DateTime('-1 hour'), new \DateTime('-3 hours'));
        $em->flush();

        $result = $service->getRoomHasStatusMap([$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertTrue($result[$room->getId()]);
    }

    public function testGetRoomHasStatusMapWithRoomWithoutStatus(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);
        $room = $this->createRoom($em, 'HasStatusEmptyRoom', new \DateTime('-2 hours'));

        $result = $service->getRoomHasStatusMap([$room->getId()]);

        $this->assertArrayNotHasKey($room->getId(), $result);
    }

    public function testGetRoomHasStatusMapWithEmptyArray(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $result = $service->getRoomHasStatusMap([]);

        $this->assertEmpty($result);
    }

    public function testGetRoomHasStatusMapWithNonExistentRoom(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $result = $service->getRoomHasStatusMap([-1]);

        $this->assertEmpty($result);
    }

    public function testGetRoomHasStatusMapBatchOfDifferentStates(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $service = $this->getContainer()->get(RoomStatusFrontendService::class);

        $activeRoom = $this->createRoom($em, 'HasStatusBatchActive', new \DateTime('-1 hour'));
        $this->createStatus($em, $activeRoom, 'has-batch-active@test.de', null);
        $em->flush();

        $destroyedRoom = $this->createRoom($em, 'HasStatusBatchDestroyed', new \DateTime('-3 hours'));
        $this->createStatus($em, $destroyedRoom, 'has-batch-destroyed@test.de', true, new \DateTime('-1 hour'), new \DateTime('-3 hours'));
        $em->flush();

        $emptyRoom = $this->createRoom($em, 'HasStatusBatchEmpty', new \DateTime('-2 hours'));

        $result = $service->getRoomHasStatusMap([$activeRoom->getId(), $destroyedRoom->getId(), $emptyRoom->getId()]);

        $this->assertArrayHasKey($activeRoom->getId(), $result);
        $this->assertTrue($result[$activeRoom->getId()]);
        $this->assertArrayHasKey($destroyedRoom->getId(), $result);
        $this->assertTrue($result[$destroyedRoom->getId()]);
        $this->assertArrayNotHasKey($emptyRoom->getId(), $result);
    }
}
