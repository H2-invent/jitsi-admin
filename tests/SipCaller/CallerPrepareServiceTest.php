<?php

namespace App\Tests\SipCaller;

use App\Entity\CallerRoom;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerPrepareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CallerPrepareServiceTest extends KernelTestCase
{
    public function testRandomId(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $callerId = new CallerRoom();
        $callerId->setRoom($room);
        $callerId->setCallerId('123456');
        $callerId->setCreatedAt(new \DateTime());
        $manager->persist($callerId);
        $manager->flush();
        self::assertTrue($callerPrpareService->checkRandomId('123456'));
        self::assertFalse($callerPrpareService->checkRandomId('000000'));
    }

    public function testGenerateRandomId(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $callerId = new CallerRoom();
        $callerId->setRoom($room);
        $callerId->setCallerId('1');
        $callerId->setCreatedAt(new \DateTime());
        $manager->persist($callerId);
        $manager->flush();
        $callerPrpareService->generateRoomId(1);
        self::assertEquals('0', $callerPrpareService->generateRoomId(1));
    }

    public function testGenerateRandomIdGen(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $callerId = new CallerRoom();
        $callerId->setRoom($room);
        $callerId->setCallerId('1');
        $callerId->setCreatedAt(new \DateTime());
        $manager->persist($callerId);
        $manager->flush();
        $callerPrpareService->generateRoomId(1);
        self::assertEquals(6, strlen($callerPrpareService->generateRoomId(999999)));
        self::assertEquals(5, strlen($callerPrpareService->generateRoomId(99999)));
        self::assertEquals(4, strlen($callerPrpareService->generateRoomId(9999)));
        self::assertEquals(3, strlen($callerPrpareService->generateRoomId(999)));
    }
    public function testAddCallerIdToRoom(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        self::assertNull($room->getCallerRoom());
        $callerPrpareService->addCallerIdToRoom($room);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        self::assertNotNull($room->getCallerRoom());
        $id = $room->getCallerRoom();
        self::assertEquals($id, $callerPrpareService->addCallerIdToRoom($room));
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        self::assertEquals($id, $room->getCallerRoom());
    }

    public function testAddCallerIdToFutureRoom(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        self::assertEquals(27, sizeof($callerPrpareService->addNewId()));
        self::assertEquals(0, sizeof($callerPrpareService->addNewId()));
    }
    public function testDeleteCallerIdfromPastRoom(): void
    {
        $kernel = self::bootKernel();
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        self::assertEquals(2, sizeof($callerPrpareService->deleteOldId()));
        self::assertEquals(0, sizeof($callerPrpareService->deleteOldId()));
    }

    public function testPrepareCallerCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:caller:prepare');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [OK] We added to all Rooms which had no caller-Id a caller-Id', $output);
        $callerPrpareService = self::getContainer()->get(CallerPrepareService::class);

        self::assertEquals(0, sizeof($callerPrpareService->deleteOldId()));
        self::assertEquals(0, sizeof($callerPrpareService->addNewId()));
    }
}
