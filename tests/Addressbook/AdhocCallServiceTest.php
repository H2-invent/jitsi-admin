<?php

namespace App\Tests\Addressbook;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\adhocmeeting\AdhocCallService;
use App\Service\Lobby\DirectSendService;
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
            ->setCreatedAt(new \DateTimeImmutable())
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

    public function testMarkDeclinedNotifiesCallerAndEndsRoom(): void
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
            ->setCreatedAt(new \DateTimeImmutable())
            ->setInvitedFrom($caller)
            ->setUid('adhoc-mark-declined')
            ->setState(CalloutSession::$RINGING)
            ->setLeftRetries(0);
        $em->persist($session);
        $em->flush();

        $directSend = $this->createMock(DirectSendService::class);
        $directSend->expects(self::once())
            ->method('sendAdhocCallFailed')
            ->with('personal/' . $caller->getUid(), $room->getId(), 'declined');
        $directSend->expects(self::once())
            ->method('sendCloseDialog')
            ->with('personal/' . $callee->getUid());

        $adhocCallService = new AdhocCallService($em, $directSend);
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);

        self::assertTrue($adhocCallService->markDeclined($callee, $room));
        self::assertNull($calloutRepo->findOneBy(['uid' => 'adhoc-mark-declined']));
        self::assertLessThanOrEqual(time(), (int)$room->getEnddate()->format('U'));
        // A second decline is a no-op because the session is already gone.
        self::assertFalse($adhocCallService->markDeclined($callee, $room));
    }

    public function testMarkTimedOutNotifiesCallerStopsRingAndEndsRoom(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $session = $this->createSession($em, $room, $callee, $caller, 'adhoc-timeout-callee', CalloutSession::$RINGING);

        $directSend = $this->createMock(DirectSendService::class);
        $directSend->expects(self::once())
            ->method('sendAdhocCallFailed')
            ->with('personal/' . $caller->getUid(), $room->getId(), 'timeout');
        $directSend->expects(self::once())
            ->method('sendCloseDialog')
            ->with('personal/' . $callee->getUid());

        $adhocCallService = new AdhocCallService($em, $directSend);
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);

        self::assertTrue($adhocCallService->markTimedOut($callee, $room));
        self::assertNull($calloutRepo->findOneBy(['uid' => 'adhoc-timeout-callee']));
        self::assertLessThanOrEqual(time(), (int)$room->getEnddate()->format('U'));
        // A second timeout is a no-op because the session is already gone.
        self::assertFalse($adhocCallService->markTimedOut($callee, $room));
    }

    public function testCancelPendingCallsByInviterStopsRingingCallees(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee1 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $callee2 = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $room1 = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room2 = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);

        $waiting1 = $this->createSession($em, $room1, $callee1, $caller, 'adhoc-cancel-1', CalloutSession::$RINGING);
        $waiting2 = $this->createSession($em, $room2, $callee2, $caller, 'adhoc-cancel-2', CalloutSession::$RINGING);
        // Already finished session must be left untouched.
        $finished = $this->createSession($em, $room1, $callee1, $caller, 'adhoc-cancel-3', CalloutSession::$TIMEOUT);

        $closedTopics = [];
        $directSend = $this->createMock(DirectSendService::class);
        $directSend->expects(self::exactly(2))
            ->method('sendCloseDialog')
            ->willReturnCallback(function ($topic) use (&$closedTopics) {
                $closedTopics[] = $topic;
            });

        $adhocCallService = new AdhocCallService($em, $directSend);
        self::assertSame(2, $adhocCallService->cancelPendingCallsByInviter($caller));

        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);
        self::assertNull($calloutRepo->findOneBy(['uid' => 'adhoc-cancel-1']));
        self::assertNull($calloutRepo->findOneBy(['uid' => 'adhoc-cancel-2']));
        self::assertNotNull($calloutRepo->findOneBy(['uid' => 'adhoc-cancel-3']));
        self::assertEqualsCanonicalizing(
            ['personal/' . $callee1->getUid(), 'personal/' . $callee2->getUid()],
            $closedTopics
        );
        self::assertLessThanOrEqual(time(), (int)$room1->getEnddate()->format('U'));
        self::assertLessThanOrEqual(time(), (int)$room2->getEnddate()->format('U'));
    }

    private function createSession(EntityManagerInterface $em, Rooms $room, User $callee, User $caller, string $uid, int $state): CalloutSession
    {
        $session = new CalloutSession();
        $session->setUser($callee)
            ->setRoom($room)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setInvitedFrom($caller)
            ->setUid($uid)
            ->setState($state)
            ->setLeftRetries(0);
        $em->persist($session);
        $em->flush();

        return $session;
    }
}
