<?php

namespace App\Tests\Addressbook;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Message\AdhocCallTimeoutMessage;
use App\MessageHandler\AdhocCallTimeoutHandler;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdhocCallTimeoutHandlerTest extends KernelTestCase
{
    private function createWaitingSession(EntityManagerInterface $em, Rooms $room, User $caller, User $callee, string $uid): CalloutSession
    {
        $session = new CalloutSession();
        $session->setUser($callee)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($caller)
            ->setUid($uid)
            ->setState(CalloutSession::$RINGING)
            ->setLeftRetries(0);
        $em->persist($session);
        $em->flush();

        return $session;
    }

    public function testTimeoutNotifiesCallerAndDismissesCallee(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $session = $this->createWaitingSession($em, $room, $caller, $callee, 'adhoc-timeout-active');

        $directSend = $this->createMock(DirectSendService::class);
        $directSend->expects(self::once())
            ->method('sendAdhocCallFailed')
            ->with('personal/' . $caller->getUid(), $room->getId());
        $directSend->expects(self::once())
            ->method('sendCloseDialog')
            ->with('personal/' . $callee->getUid());

        $handler = new AdhocCallTimeoutHandler($em, $directSend, new NullLogger());
        $handler(new AdhocCallTimeoutMessage($session->getUid()));

        self::assertEquals(CalloutSession::$TIMEOUT, $session->getState());
        self::assertLessThanOrEqual(time(), (int)$room->getEnddate()->format('U'));
    }

    public function testTimeoutDoesNothingWhenCallWasAnswered(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $session = $this->createWaitingSession($em, $room, $caller, $callee, 'adhoc-timeout-answered');
        // Answering removes the session.
        $em->remove($session);
        $em->flush();

        $directSend = $this->createMock(DirectSendService::class);
        $directSend->expects(self::never())->method('sendAdhocCallFailed');
        $directSend->expects(self::never())->method('sendCloseDialog');

        $handler = new AdhocCallTimeoutHandler($em, $directSend, new NullLogger());
        $handler(new AdhocCallTimeoutMessage('adhoc-timeout-answered'));

        self::assertNull(self::getContainer()->get(CalloutSessionRepository::class)->findOneBy(['uid' => 'adhoc-timeout-answered']));
    }
}
