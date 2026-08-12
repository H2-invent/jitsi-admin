<?php

namespace App\Tests\Dashboard;

use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Repository\RoomsRepository;
use App\Repository\SchedulingTimeUserRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchedulingTimeUserRepositoryTest extends KernelTestCase
{
    private function createVote(
        EntityManagerInterface $em,
        RoomsRepository $roomRepo,
        string $roomName,
        object $user,
        int $accept
    ): void {
        $room = $roomRepo->findOneBy(['name' => $roomName]);
        $this->assertNotNull($room, "Room '$roomName' must exist");

        $scheduling = $em->getRepository(Scheduling::class)->findOneBy(['room' => $room]);
        $this->assertNotNull($scheduling, "Scheduling for '$roomName' must exist");

        $time = $em->getRepository(SchedulingTime::class)->findOneBy(['scheduling' => $scheduling]);
        $this->assertNotNull($time, "SchedulingTime for '$roomName' must exist");

        $vote = new SchedulingTimeUser();
        $vote->setUser($user)
            ->setScheduleTime($time)
            ->setAccept($accept);
        $em->persist($vote);
    }

    public function testFindVotesForUserAndRoomsReturnsRoomsUserHasVotedOn(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $repo = $this->getContainer()->get(SchedulingTimeUserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        $this->assertNotNull($room);

        $this->createVote($em, $roomRepo, 'Termin finden: 0', $user, 1);
        $em->flush();

        $result = $repo->findVotesForUserAndRooms($user, [$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $result);
        $this->assertTrue($result[$room->getId()]);
    }

    public function testFindVotesForUserAndRoomsReturnsEmptyWhenNoVotes(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $repo = $this->getContainer()->get(SchedulingTimeUserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'Termin finden: 1']);
        $this->assertNotNull($room);

        $result = $repo->findVotesForUserAndRooms($user, [$room->getId()]);

        $this->assertEmpty($result);
    }

    public function testFindVotesForUserAndRoomsReturnsEmptyForEmptyRoomIds(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $userRepo = $this->getContainer()->get(UserRepository::class);
        $repo = $this->getContainer()->get(SchedulingTimeUserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);

        $result = $repo->findVotesForUserAndRooms($user, []);

        $this->assertEmpty($result);
    }

    public function testFindVotesForUserAndRoomsOnlyReturnsRoomsForGivenUser(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $repo = $this->getContainer()->get(SchedulingTimeUserRepository::class);

        $user1 = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);

        $room = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);

        $this->createVote($em, $roomRepo, 'Termin finden: 0', $user1, 1);
        $em->flush();

        $resultUser1 = $repo->findVotesForUserAndRooms($user1, [$room->getId()]);
        $resultUser2 = $repo->findVotesForUserAndRooms($user2, [$room->getId()]);

        $this->assertArrayHasKey($room->getId(), $resultUser1);
        $this->assertEmpty($resultUser2, 'User2 has not voted, so the map must be empty');
    }

    public function testFindVotesForUserAndRoomsReturnsMultipleRooms(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $repo = $this->getContainer()->get(SchedulingTimeUserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room0 = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);
        $room1 = $roomRepo->findOneBy(['name' => 'Termin finden: 1']);
        $room2 = $roomRepo->findOneBy(['name' => 'Termin finden: 2']);

        $this->createVote($em, $roomRepo, 'Termin finden: 0', $user, 1);
        $this->createVote($em, $roomRepo, 'Termin finden: 2', $user, 1);
        $em->flush();

        $result = $repo->findVotesForUserAndRooms($user, [$room0->getId(), $room1->getId(), $room2->getId()]);

        $this->assertArrayHasKey($room0->getId(), $result);
        $this->assertArrayHasKey($room2->getId(), $result);
        $this->assertArrayNotHasKey($room1->getId(), $result);
    }

    public function testFindVotesForUserAndRoomsMatchesSingleRoomFinder(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $repo = $this->getContainer()->get(SchedulingTimeUserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $room = $roomRepo->findOneBy(['name' => 'Termin finden: 0']);

        $this->createVote($em, $roomRepo, 'Termin finden: 0', $user, 1);
        $em->flush();

        $single = $repo->findVotesForUserAndRoom($room, $user);
        $batch = $repo->findVotesForUserAndRooms($user, [$room->getId()]);

        $this->assertNotEmpty($single);
        $this->assertArrayHasKey($room->getId(), $batch);
        $this->assertTrue($batch[$room->getId()]);
    }
}
