<?php

namespace App\Tests\SipCaller;

use App\Entity\CallerId;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerPrepareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CallerIdPrepareTest extends KernelTestCase
{
    public function testAddCallerIdCheckRandom(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $callerId = new CallerId();
        $callerId->setRoom($room)->setCallerId('10')->setUser($room->getModerator())->setCreatedAt(new \DateTime());
        $manager->persist($callerId);
        $manager->flush();
        for ($i = 0; $i < 10; $i++) {
            self::assertEquals(false, $callerPrpareService->checkRandomCallerUserId('0' . $i, $room));
        }
        self::assertEquals(true, $callerPrpareService->checkRandomCallerUserId('10', $room));
    }

    public function testAddCallerIdgenerateCallerId(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $callerId = new CallerId();
        $callerId->setRoom($room)->setCallerId('1')->setUser($room->getModerator())->setCreatedAt(new \DateTime());
        $manager->persist($callerId);
        $manager->flush();

        self::assertEquals('0', $callerPrpareService->generateCallerUserId($room, 1));
    }

    public function testAddCallerIdToRoom(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, sizeof($callerPrpareService->createUserCallerIDforRoom($room)));
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, sizeof($room->getCallerIds()));
    }

    public function testAddCallerId(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        self::assertEquals(48, sizeof($callerPrpareService->createUserCallerId()));
    }
}
