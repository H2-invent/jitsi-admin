<?php

namespace App\Tests\Server;

use App\Entity\RoomStatusParticipant;
use App\Repository\ServerRepository;
use App\Service\ServerService;
use App\Service\ServerUserManagment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServerActualConferenceTest extends KernelTestCase
{
    public function testActiveRooms(): void
    {
        $kernel = self::bootKernel();
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si2']);
        $this->assertSame('test', $kernel->getEnvironment());
        $serverService = self::getContainer()->get(ServerUserManagment::class);
        self::assertEquals(1, sizeof($serverService->getActualConference($server)));

        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        self::assertEquals(0, sizeof($serverService->getActualConference($server)));
    }

    public function testActiveRoomsParticipants(): void
    {
        $kernel = self::bootKernel();
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si2']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $serverService = self::getContainer()->get(ServerUserManagment::class);
        $part = $serverService->getActualParticipantsFromServer($server)[0];
        self::assertEquals(1, sizeof($serverService->getActualParticipantsFromServer($server)));

        $roomPart = new RoomStatusParticipant();
        $roomPart->setInRoom(true)
            ->setEnteredRoomAt(new \DateTime())
            ->setRoomStatus($part->getRoomStatus())
            ->setParticipantId('test123')
            ->setParticipantName('test12354');
        $manager->persist($roomPart);
        $manager->flush();
        self::assertEquals(2, sizeof($serverService->getActualParticipantsFromServer($server)));

        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        self::assertEquals(0, sizeof($serverService->getActualParticipantsFromServer($server)));
    }
}
