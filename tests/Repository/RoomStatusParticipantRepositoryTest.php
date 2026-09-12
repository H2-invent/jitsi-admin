<?php

namespace App\Tests\Repository;

use App\Entity\Rooms;
use App\Entity\RoomStatusParticipant;
use App\Repository\RoomStatusParticipantRepository;
use App\Repository\RoomsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomStatusParticipantRepositoryTest extends KernelTestCase
{
    private RoomStatusParticipantRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(RoomStatusParticipantRepository::class);
    }

    private function runningRoom(): Rooms
    {
        return self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Running Room']);
    }

    public function testFindOccupantsOfRoomReturnsOnlyParticipantsCurrentlyInTheRoom(): void
    {
        $participants = $this->repository->findOccupantsOfRoom($this->runningRoom());

        $this->assertCount(1, $participants);
        $this->assertSame('inderKonferenz@test.de', $participants[0]->getParticipantId());
    }

    public function testFindActualParticipantsByServerReturnsParticipantsInTheRoom(): void
    {
        $room = $this->runningRoom();

        $participants = $this->repository->findActualParticipantsByServer($room->getServer());

        $this->assertNotEmpty($participants);
        foreach ($participants as $participant) {
            $this->assertTrue($participant->getInRoom());
        }
        $participantIds = array_map(static fn (RoomStatusParticipant $p) => $p->getParticipantId(), $participants);
        $this->assertContains('inderKonferenz@test.de', $participantIds);
    }

    public function testFindParticipantsByServerReturnsParticipantsWithinTheDateRange(): void
    {
        $room = $this->runningRoom();

        $participants = $this->repository->findParticipantsByServer(
            $room->getServer(),
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
        );

        $this->assertNotEmpty($participants);
        foreach ($participants as $participant) {
            $this->assertInstanceOf(RoomStatusParticipant::class, $participant);
        }
    }

    public function testFindUniqueParticipantsByRoomReturnsOneRowPerParticipantId(): void
    {
        $participants = $this->repository->findUniqueParticipantsByRoom($this->runningRoom());

        $participantIds = array_map(static fn (RoomStatusParticipant $p) => $p->getParticipantId(), $participants);
        sort($participantIds);

        $this->assertSame(['inderKonferenz3@test.de', 'inderKonferenz@test.de'], $participantIds);
    }
}
