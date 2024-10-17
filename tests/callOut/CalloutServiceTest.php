<?php

namespace App\Tests\callOut;

use App\Entity\CallerId;
use App\Entity\CallerSession;
use App\Entity\CalloutSession;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Callout\CalloutService;
use App\Service\Callout\CalloutServiceDialSuccessfull;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function PHPUnit\Framework\assertEquals;

class CalloutServiceTest extends KernelTestCase
{
    public function testCreateNewCallout(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room, $user));
        self::assertNull($calloutService->checkCallIn($room, $user));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNotNull($callout);
        self::assertEquals(2, $callout->getLeftRetries());
        self::assertEquals($callout, $calloutService->checkCallout($room, $user));
        self::assertNull($calloutService->checkCallIn($room, $user));
        self::assertEquals(1, sizeof($callOurRepo->findAll()));
        self::assertEquals($callout, $calloutService->createCallout($room, $user, $inviter));
        self::assertEquals(1, sizeof($callOurRepo->findAll()));
    }

    public function testCreateNewCalloutFailSameEmail(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room, $user));
        self::assertNull($calloutService->checkCallIn($room, $user));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNull($callout);
    }

    public function testCreateNewCalloutFailsNotAllowed(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room, $user));
        self::assertNull($calloutService->checkCallIn($room, $user));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNull($callout);
    }

    public function testreturnCallerId(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        self::assertEquals('987654321012', $calloutService->getCallerIdForUser($user));
    }

    public function testreturnnoCallerId(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'user@local.de']);
        self::assertNull($calloutService->getCallerIdForUser($user));
    }

    public function testisAllowedtoBeCalled(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        self::assertTrue($calloutService->isAllowedToBeCalled($user));
    }

    public function testisAllowedtoBeCalledNoUser(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@localfalse.de']);
        self::assertFalse($calloutService->isAllowedToBeCalled($user));
    }

    public function testisAllowedtoBeCalledNoLDAP(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        self::assertFalse($calloutService->isAllowedToBeCalled($user));
    }

    public function testisAllowedtoBeCalledNoPhoneNumber(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        $user->setSpezialProperties(['noPhoneNumber' => '123456']);
        self::assertFalse($calloutService->isAllowedToBeCalled($user));
    }

    public function testrefreshCallout(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room, $user));
        self::assertNull($calloutService->checkCallIn($room, $user));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNotNull($callout);
        self::assertEquals(2, $callout->getLeftRetries());
        $callout->setState(CalloutSession::$ON_HOLD);
        $manager->persist($callout);
        $manager->flush();
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNotNull($callout);
        self::assertEquals(1, $callout->getLeftRetries());
        $callout->setState(CalloutSession::$LATER);
        $manager->persist($callout);
        $manager->flush();
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNotNull($callout);
        self::assertEquals(0, $callout->getLeftRetries());
        $callout->setState(CalloutSession::$OCCUPIED);
        $manager->persist($callout);
        $manager->flush();
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNotNull($callout);
        $callout = $calloutService->checkCallout($room, $user);
        self::assertEquals(0, $callout->getLeftRetries());
        self::assertNull($calloutService->checkCallIn($room, $user));
    }

    public function testDialSucessfull()
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $calloutService = self::getContainer()->get(CalloutService::class);
        $calloutServiceDialSuccessfull = self::getContainer()->get(CalloutServiceDialSuccessfull::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room, $user));
        self::assertNull($calloutService->checkCallIn($room, $user));
        self::assertFalse($calloutServiceDialSuccessfull->dialSuccessfull($user, $room));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNotNull($callout);
        assertEquals(CalloutSession::$INITIATED, $callout->getState());
        self::assertFalse($calloutServiceDialSuccessfull->dialSuccessfull($user, $room));
        $callout->setState(CalloutSession::$DIALED);
        $manager->persist($callout);
        $manager->flush();
        self::assertTrue($calloutServiceDialSuccessfull->dialSuccessfull($user, $room));
        self::assertFalse($calloutServiceDialSuccessfull->dialSuccessfull($user, $room));

    }

    public function testcheckCallIn()
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $calloutService = self::getContainer()->get(CalloutService::class);
        $calloutServiceDialSuccessfull = self::getContainer()->get(CalloutServiceDialSuccessfull::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $callerId = new CallerId();
        $callerId->setUser($user)
        ->setRoom($room)
       ->setCallerId('test')
            ->setCreatedAt(new \DateTime());
        $manager->persist($callerId);
        $manager->flush();
        $callerSession = new CallerSession();
        $callerSession->setCaller($callerId)
            ->setCallerId('test123')
            ->setSessionId('test1234')
            ->setAuthOk(true)
            ->setCreatedAt(new \DateTime());
        $manager->persist($callerSession);
        $manager->flush();
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room, $user));
        self::assertNotNull($calloutService->checkCallIn($room, $user));
        self::assertFalse($calloutServiceDialSuccessfull->dialSuccessfull($user, $room));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNull($callout);

    }

}
