<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\RoomStatus;
use App\Repository\CallerRoomRepository;
use Doctrine\ORM\EntityManagerInterface;
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

        self::assertEquals(
            json_encode(
                [
                    'state' => 'STARTED',
                    'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiJVc2VyMiwgVGVzdDIsIHRlc3RAbG9jYWwyLmRlIn19LCJtb2RlcmF0b3IiOmZhbHNlfQ.tBl3a2rCTYla8Bxeg3kSxPLenTgnUuZIURHQoxSPvbY',
                    'room_name' => '123456780@testdomain.com'
                ]
            ),
            $res
        );
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

        self::assertEquals(
            json_encode(
                [
                    'state' => 'STARTED',
                    'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiIwMDk4NzY1NDU1MzI1In19LCJtb2RlcmF0b3IiOmZhbHNlfQ.f_A4MtvIEUJ06FWTmimXbCiEP98JcYrHfYJAabNu29M',
                    'room_name' => '123456780@testdomain.com'
                ]
            ),
            $res
        );
    }
}
