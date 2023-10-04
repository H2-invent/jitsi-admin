<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\RoomStatus;
use App\Repository\CallerRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
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

        self::assertEquals(
            json_encode(
                [
                    'state' => 'STARTED',
                    'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiIxMjM0NTYyMjU1NjYifX0sIm1vZGVyYXRvciI6ZmFsc2V9.cd8QFXA3LnS54ESBAFR4iGOQuMtz1nQZ7snqEjSjivo',
                    'room_name' => '123456780@testdomain.com'
                ]
            ),
            $res
        );
    }
}
