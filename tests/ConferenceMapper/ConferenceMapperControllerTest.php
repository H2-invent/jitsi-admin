<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\RoomStatus;
use App\Repository\CallerRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceMapperControllerTest extends WebTestCase
{
    public function testRoute(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $crawler = $client->request('GET', '/api/v1/conferenceMapper');
        $this->assertResponseIsSuccessful();
    }

    public function testFailedAuth(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApiFailure']);
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=12340&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(['error' => true, 'text' => 'AUTHORIZATION_FAILED']), $res);
    }

    public function testnotStarted(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(['state' => 'WAITING', 'reason' => 'NOT_STARTED']), $res);
    }

    public function testnoRoom(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(['error' => true, 'reason' => 'ROOM_NOT_FOUND']), $res);
    }

    public function testRoomStarted(): void
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

        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456225566&confid=12340');

        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        $result = json_decode($res, true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals('STARTED', $result['state']);
        self::assertEquals('123456780@testdomain.com', $result['room_name']);
        self::assertEquals('123456225566', $result['display_name']);

        // Verify JWT payload (signature will differ due to appSecret change)
        $decoded = JWT::decode($result['jwt'], new Key($callerRoom->getRoom()->getServer()->getAppSecret(), 'HS256'));
        $expectedSubset = [
            'aud' => 'jitsi_admin',
            'iss' => 'jitsiId',
            'sub' => 'meet.jit.si2',
            'room' => '123456780',
            'moderator' => false,
            'lobbyModerator' => false,
        ];
        foreach ($expectedSubset as $key => $val) {
            self::assertEquals($val, $decoded->$key, "JWT payload key '$key' mismatch");
        }
    }
}
