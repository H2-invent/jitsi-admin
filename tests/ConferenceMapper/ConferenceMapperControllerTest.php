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
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer TestApi'));
        $crawler = $client->request('GET', '/api/v1/conferenceMapper');
        $this->assertResponseIsSuccessful();
    }

    public function testFailedAuth(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer TestApiFailure'));
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=12340&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(array('error' => true, 'text' => 'AUTHORIZATION_FAILED')), $res);
    }

    public function testnotStarted(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer TestApi'));
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(array('state' => 'PLEASE_WAIT', 'reason' => 'NOT_STARTED')), $res);
    }

    public function testnoRoom(): void
    {
        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer TestApi'));
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(array('error' => true, 'reason' => 'ROOM_NOT_FOUND')), $res);
    }

    public function testRoomStarted(): void
    {

        $client = static::createClient(array(), array('HTTP_authorization' => 'Bearer TestApi'));
        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(array('state' => 'PLEASE_WAIT', 'reason' => 'NOT_STARTED')), $res);


        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(array('callerId' => '12340'));

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

        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=123456&confid=12340');
        $res = $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(array(
                    'state' => 'STARTED',
                    'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiIxMjM0NTYifX0sIm1vZGVyYXRvciI6ZmFsc2V9.QfpvUo2wz-XAdcY--jD5_75ZMQxqz6c5_V9VmjjpCS8'
                )
            )
            , $res);

    }

}
