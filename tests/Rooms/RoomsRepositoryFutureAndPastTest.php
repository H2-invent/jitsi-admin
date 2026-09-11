<?php

namespace App\Tests\Rooms;

use App\Entity\Deputy;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomsRepositoryFutureAndPastTest extends KernelTestCase
{
    public function testFindRoomsFutureAndPastReturnsOnlyRoomsForGivenUser(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);

        $ownUser = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $otherUser = $userRepo->findOneBy(['email' => 'test@australia.de']);
        $this->assertNotNull($ownUser);
        $this->assertNotNull($otherUser);

        $ownRoom = $this->createRoom($em, 'FutureAndPast Own Room', $ownUser);
        $otherRoom = $this->createRoom($em, 'FutureAndPast Other Room', $otherUser);
        $em->flush();

        $result = $roomRepo->findRoomsFutureAndPast($ownUser, '-1 month');

        $resultIds = array_map(static fn (Rooms $room) => $room->getId(), $result);
        $this->assertContains($ownRoom->getId(), $resultIds);
        $this->assertNotContains($otherRoom->getId(), $resultIds);

        foreach ($result as $room) {
            $this->assertTrue(
                $room->getUser()->contains($ownUser),
                'Every returned room must belong to the given user'
            );
        }
    }

    public function testFindRoomsFutureAndPastDoesNotReturnRoomsWhereUserIsDeputy(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);

        $manager = $userRepo->findOneBy(['email' => 'test@australia.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@noTimeZone.de']);
        $this->assertNotNull($manager);
        $this->assertNotNull($deputy);

        $existingDeputyRelation = $em->getRepository(Deputy::class)->findOneBy([
            'manager' => $manager,
            'deputy' => $deputy,
        ]);
        if ($existingDeputyRelation === null) {
            $deputyRelation = new Deputy();
            $deputyRelation->setManager($manager)
                ->setDeputy($deputy)
                ->setIsFromLdap(false)
                ->setCreatedAt(new \DateTime());
            $em->persist($deputyRelation);
        }

        $managerRoom = $this->createRoom($em, 'FutureAndPast Manager Room', $manager);
        $deputyOwnRoom = $this->createRoom($em, 'FutureAndPast Deputy Own Room', $deputy);
        $em->flush();

        $deputyResult = $roomRepo->findRoomsFutureAndPast($deputy, '-1 month');
        $deputyResultIds = array_map(static fn (Rooms $room) => $room->getId(), $deputyResult);
        $this->assertContains($deputyOwnRoom->getId(), $deputyResultIds);
        $this->assertNotContains(
            $managerRoom->getId(),
            $deputyResultIds,
            'Rooms of the manager must not be returned when the user is only set as deputy'
        );

        $managerResult = $roomRepo->findRoomsFutureAndPast($manager, '-1 month');
        $managerResultIds = array_map(static fn (Rooms $room) => $room->getId(), $managerResult);
        $this->assertContains($managerRoom->getId(), $managerResultIds);
    }

    public function testFindRoomsFutureAndPastHonorsTimeBackParameter(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());

        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $userRepo = $this->getContainer()->get(UserRepository::class);

        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $this->assertNotNull($user);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $futureRoom = $this->createRoomAt($em, 'FutureAndPast Future Room', $user, $now->modify('+2 days'), $now->modify('+3 days'));
        $recentPastRoom = $this->createRoomAt($em, 'FutureAndPast Recent Past Room', $user, $now->modify('-2 days'), $now->modify('-1 day'));
        $oldPastRoom = $this->createRoomAt( $em, 'FutureAndPast Old Past Room', $user, $now->modify('-70 days'), $now->modify('-69 days'));
        $em->flush();

        $result = $roomRepo->findRoomsFutureAndPast($user, '-1 month');

        $resultIds = array_map(static fn (Rooms $room) => $room->getId(), $result);
        $this->assertContains($futureRoom->getId(), $resultIds);
        $this->assertContains($recentPastRoom->getId(), $resultIds);
        $this->assertNotContains(
            $oldPastRoom->getId(),
            $resultIds,
            'Rooms that ended before the timeBack window must not be returned'
        );
    }

    private function createRoom(EntityManagerInterface $em, string $name, User $participant): Rooms
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        return $this->createRoomAt($em, $name, $participant, $now->modify('+1 day'), $now->modify('+2 days'));
    }

    private function createRoomAt(
        EntityManagerInterface $em,
        string $name,
        User $participant,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): Rooms {
        $server = $em->getRepository(Server::class)->findOneBy(['serverName' => 'Server without License']);
        $this->assertNotNull($server);

        $room = new Rooms();
        $room->setTimeZone('UTC');
        $room->setStart(\DateTime::createFromImmutable($start));
        $room->setEnddate(\DateTime::createFromImmutable($end));
        $room->setName($name);
        $room->setUid('uid-' . md5(uniqid((string) $name, true)));
        $room->setUidReal('uidreal-' . md5(uniqid((string) $name, true)));
        $room->setSequence(0);
        $room->setDuration(60);
        $room->setAgenda('Agenda of ' . $name);
        $room->setScheduleMeeting(false);
        $room->setPersistantRoom(false);
        $room->setServer($server);
        $room->addUser($participant);

        $em->persist($room);

        return $room;
    }
}
