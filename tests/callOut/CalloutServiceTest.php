<?php

namespace App\Tests\callOut;

use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Callout\CalloutService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CalloutServiceTest extends KernelTestCase
{
    public function testCreateNewCallout(): void
    {
        $kernel = self::bootKernel();

        $calloutService = self::getContainer()->get(CalloutService::class);
        $callOurRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $inviter = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $user = $userRepo->findOneBy(array('email'=>'test@local4.de'));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        self::assertEquals(0, sizeof($callOurRepo->findAll()));
        $callout = $calloutService->createCallout($room,$user,$inviter);
        self::assertNotNull($callout);
        self::assertEquals(1, sizeof($callOurRepo->findAll()));
        self::assertEquals($callout, $calloutService->createCallout($room,$user,$inviter));
        self::assertEquals(1, sizeof($callOurRepo->findAll()));
    }
}
