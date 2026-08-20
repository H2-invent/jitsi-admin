<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\RoomStatus;
use App\Repository\CallerRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceMapperControllerCallerIdTest extends WebTestCase
{


    public function testRoomStartedCallerIdtoNameFound(): void
    {

        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(['state' => 'WAITING', 'reason' => 'NOT_STARTED']), $res);

        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => '12340']);

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $status = new RoomStatus();
        $status->setRoom($callerRoom->getRoom())
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $callerRoom->getRoom()->getServer()->setJigasiProsodyDomain('testdomain.com');
        $manager->flush();
        $callerRoom->getRoom()->addRoomstatus($status);

        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=009876543210&confid=12340');

        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        $result = json_decode($res, true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals('STARTED', $result['state']);
        self::assertEquals('123456780@testdomain.com', $result['room_name']);
        self::assertEquals('User2, Test2, test@local2.de', $result['display_name']);

        $decoded = JWT::decode($result['jwt'], new Key($callerRoom->getRoom()->getServer()->getAppSecret(), 'HS256'));
        self::assertEquals('jitsi_admin', $decoded->aud);
        self::assertEquals('jitsiId', $decoded->iss);
        self::assertEquals('meet.jit.si2', $decoded->sub);
        self::assertEquals('123456780', $decoded->room);
        self::assertEquals('User2, Test2, test@local2.de', $decoded->context->user->name);
    }

    public function testRoomStartedCallerIdtoNameNotFound(): void
    {

        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(['state' => 'WAITING', 'reason' => 'NOT_STARTED']), $res);

        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => '12340']);

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $status = new RoomStatus();
        $status->setRoom($callerRoom->getRoom())
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $callerRoom->getRoom()->getServer()->setJigasiProsodyDomain('testdomain.com');
        $manager->flush();
        $callerRoom->getRoom()->addRoomstatus($status);

        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=0098765455325&confid=12340');

        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        $result = json_decode($res, true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals('STARTED', $result['state']);
        self::assertEquals('123456780@testdomain.com', $result['room_name']);
        self::assertEquals('0098765455325', $result['display_name']);

        $decoded = JWT::decode($result['jwt'], new Key($callerRoom->getRoom()->getServer()->getAppSecret(), 'HS256'));
        self::assertEquals('jitsi_admin', $decoded->aud);
        self::assertEquals('jitsiId', $decoded->iss);
        self::assertEquals('meet.jit.si2', $decoded->sub);
        self::assertEquals('123456780', $decoded->room);
        self::assertEquals('0098765455325', $decoded->context->user->name);
    }
}
