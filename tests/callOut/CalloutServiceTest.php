<?php

namespace App\Tests\callOut;

use App\Entity\CalloutSession;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Callout\CalloutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CalloutServiceTest extends KernelTestCase
{
    public function testCreateNewCallout(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user = $userRepo->findOneBy(array('email' => 'ldapUser@local.de'));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room,$user));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNotNull($callout);
        self::assertEquals(2, $callout->getLeftRetries());
        self::assertEquals($callout,$calloutService->checkCallout($room,$user));
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
        $inviter = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room,$user));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNull($callout);
    }

    public function testCreateNewCalloutFailsNotAllowed(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room,$user));
        $callout = $calloutService->createCallout($room, $user, $inviter);
        self::assertNull($callout);
    }

    public function testreturnCallerId(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'ldapUser@local.de'));
        self::assertEquals('987654321012',$calloutService->getCallerIdForUser($user));
    }
    public function testreturnnoCallerId(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'user@local.de'));
        self::assertNull($calloutService->getCallerIdForUser($user));
    }

    public function testisAllowedtoBeCalled(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'ldapUser@local.de'));
        self::assertTrue($calloutService->isAllowedToBeCalled($user));
    }

    public function testisAllowedtoBeCalledNoUser(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'ldapUser@localfalse.de'));
        self::assertFalse($calloutService->isAllowedToBeCalled($user));
    }

    public function testisAllowedtoBeCalledNoLDAP(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        self::assertFalse($calloutService->isAllowedToBeCalled($user));
    }

    public function testisAllowedtoBeCalledNoPhoneNumber(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'ldapUser@local.de'));
        $user->setSpezialProperties(array('noPhoneNumber'=>'123456'));
        self::assertFalse($calloutService->isAllowedToBeCalled($user));
    }
    public function testrefreshCallout(): void
    {
        $kernel = self::bootKernel();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $user = $userRepo->findOneBy(array('email' => 'ldapUser@local.de'));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        self::assertNull($calloutService->checkCallout($room,$user));
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
        $callout = $calloutService->checkCallout($room,$user);
        self::assertEquals(0, $callout->getLeftRetries());
    }


}
