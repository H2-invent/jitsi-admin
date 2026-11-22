<?php

namespace App\Tests\SipCaller;

use App\Repository\RoomsRepository;
use App\Service\Caller\CallerLeftService;
use App\Service\Caller\CallerPinService;
use App\Service\Caller\CallerPrepareService;
use App\Service\Caller\CallerSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CallerLeftServiceTest extends KernelTestCase
{
    public function testwrongSession(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');

        self::assertTrue($callerLEftService->callerLeft('12345'));
    }
    public function testCorrectSession(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];

        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');
        self::assertNotNull($session);
        self::assertNull($callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345'));
        self::assertFalse($callerLEftService->callerLeft($session->getSessionId()));
        self::assertNotNull($callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345'));
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
}
