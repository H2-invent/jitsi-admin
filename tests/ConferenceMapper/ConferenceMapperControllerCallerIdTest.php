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
            json_encode([
                'state' => 'STARTED',
                'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMCJ9LCJ1c2VyIjp7Im5hbWUiOiJVc2VyMiwgVGVzdDIsIHRlc3RAbG9jYWwyLmRlIiwibGFuZ3VhZ2UiOiJkZSIsInRpbWV6b25lIjoiRXVyb3BlL0JlcmxpbiJ9fSwibW9kZXJhdG9yIjpmYWxzZSwibG9iYnlNb2RlcmF0b3IiOmZhbHNlLCJ0aGVtZSI6eyJjb2xvclNjaGVtZSI6ImxpZ2h0In19.hMk7SA7yY80H4bO962H_knqnej0exFz6CQDFk_Q_VO4',
                'room_name' => '123456780@testdomain.com',
                "display_name" => "User2, Test2, test@local2.de"
            ], JSON_THROW_ON_ERROR),
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
            json_encode([
                'state' => 'STARTED',
                'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMCJ9LCJ1c2VyIjp7Im5hbWUiOiIwMDk4NzY1NDU1MzI1IiwibGFuZ3VhZ2UiOiJkZSIsInRpbWV6b25lIjoiRXVyb3BlL0JlcmxpbiJ9fSwibW9kZXJhdG9yIjpmYWxzZSwibG9iYnlNb2RlcmF0b3IiOmZhbHNlLCJ0aGVtZSI6eyJjb2xvclNjaGVtZSI6ImxpZ2h0In19.aWQ0_sUgAJ0LHr61HmQjWTeZll_8awla6_Y3I1LXXgE',
                'room_name' => '123456780@testdomain.com',
                "display_name" => "0098765455325"
            ], JSON_THROW_ON_ERROR),
            $res
        );
    }
}
