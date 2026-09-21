<?php

namespace App\Tests\Addressbook;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\adhocmeeting\AdhocCallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdhocCallServiceTest extends KernelTestCase
{
    public function testMarkAnsweredRemovesWaitingSession(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $session = new CalloutSession();
        $session->setUser($callee)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($caller)
            ->setUid('adhoc-mark-answered')
            ->setState(CalloutSession::$RINGING)
            ->setLeftRetries(0);
        $em->persist($session);
        $em->flush();

        $adhocCallService = self::getContainer()->get(AdhocCallService::class);
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);

        self::assertTrue($adhocCallService->markAnswered($callee, $room));
        self::assertNull($calloutRepo->findOneBy(['uid' => 'adhoc-mark-answered']));
        // A second call is a no-op because the session is gone.
        self::assertFalse($adhocCallService->markAnswered($callee, $room));
    }

    public function testMarkAnsweredSkipsNonAdhocRooms(): void
    {
        self::bootKernel();
        $adhocCallService = self::getContainer()->get(AdhocCallService::class);

        $scheduledRoom = new Rooms();
        $scheduledRoom->setScheduleMeeting(true);
        self::assertFalse($adhocCallService->markAnswered(new User(), $scheduledRoom));

        $persistentRoom = new Rooms();
        $persistentRoom->setPersistantRoom(true);
        self::assertFalse($adhocCallService->markAnswered(new User(), $persistentRoom));
    }
}
