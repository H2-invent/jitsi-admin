<?php

namespace App\Tests\Dashboard;

use App\Entity\RoomStatus;
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
}
