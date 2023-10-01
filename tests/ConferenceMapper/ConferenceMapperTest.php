<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\Server;
use App\Repository\CallerRoomRepository;
use App\Service\api\ConferenceMapperService;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConferenceMapperTest extends KernelTestCase
{
    public function testNotStarted(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '12340';
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => $id]);
        $res = $confMapperService->checkConference($callerRoom, 'Bearer TestApi', '012345123');
        self::assertEquals(['state' => 'WAITING', 'reason' => 'NOT_STARTED'], $res);
    }

    public function testAuthFailed(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '12340';
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => $id]);
        $res = $confMapperService->checkConference($callerRoom, 'Bearer TestApiFailure', '012345123');
        self::assertEquals(['error' => true, 'text' => 'AUTHORIZATION_FAILED'], $res);
    }

    public function testnoServer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '12340';
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => $id]);
        $callerRoom->getRoom()->getServer()->setLicenseKey('test');
        $res = $confMapperService->checkConference($callerRoom, 'Bearer TestApiFailure', '012345123');
        self::assertEquals(['error' => true, 'text' => 'AUTHORIZATION_FAILED'], $res);
    }

    public function testnoCallerRoom(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '12340';

        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => $id]);

        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => '12']);
        $res = $confMapperService->checkConference($callerRoom, 'Bearer TestApi', '012345123');
        self::assertEquals(['error' => true, 'reason' => 'ROOM_NOT_FOUND'], $res);
    }

    public function testStarted(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '12340';
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => $id]);

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $status = new RoomStatus();
        $status->setRoom($callerRoom->getRoom())
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();
        $callerRoom->getRoom()->addRoomstatus($status);
        $callerRoom->getRoom()->getServer()->setJigasiProsodyDomain('testdomain.com');
        $res = $confMapperService->checkConference($callerRoom, 'Bearer TestApi', '012345123');
        $jwtService = self::getContainer()->get(RoomService::class);
        $jwt = $jwtService->generateJwt($callerRoom->getRoom(), null, '012345123');

        self::assertEquals(
            [
                'state' => 'STARTED',
                'jwt' => $jwt,
                'room_name' => '123456780@testdomain.com'],
            $res
        );
    }



}
