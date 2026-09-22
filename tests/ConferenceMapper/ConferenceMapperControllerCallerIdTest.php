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
        $res = (string) $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(['state' => 'WAITING', 'reason' => 'NOT_STARTED']), $res);

        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => '12340']);

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $status = new RoomStatus();
        $status->setRoom($callerRoom->getRoom())
            ->setCreatedAt(new \DateTimeImmutable())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($status);
        $callerRoom->getRoom()->getServer()->setJigasiProsodyDomain('testdomain.com');
        $manager->flush();
        $callerRoom->getRoom()->addRoomstatus($status);

        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=009876543210&confid=12340');

        $res = (string) $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(
            json_encode([
                'state' => 'STARTED',
                'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMCIsImlzRTJFRUVuYWJsZWQiOmZhbHNlfSwidXNlciI6eyJuYW1lIjoiVXNlcjIsIFRlc3QyLCB0ZXN0QGxvY2FsMi5kZSIsImxhbmd1YWdlIjoiZGUiLCJ0aW1lem9uZSI6IkV1cm9wZS9CZXJsaW4ifX0sIm1vZGVyYXRvciI6ZmFsc2UsImxvYmJ5TW9kZXJhdG9yIjpmYWxzZSwidGhlbWUiOnsiY29sb3JTY2hlbWUiOiJsaWdodCJ9fQ.7rT0ArZBNLIbi6fzqzftbf4N1XBTZd9ht9x1hMnQegg',
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
        $res = (string) $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(json_encode(['state' => 'WAITING', 'reason' => 'NOT_STARTED']), $res);

        $callerRoomRepo = self::getContainer()->get(CallerRoomRepository::class);
        $callerRoom = $callerRoomRepo->findOneBy(['callerId' => '12340']);

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $status = new RoomStatus();
        $status->setRoom($callerRoom->getRoom())
            ->setCreatedAt(new \DateTimeImmutable())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($status);
        $callerRoom->getRoom()->getServer()->setJigasiProsodyDomain('testdomain.com');
        $manager->flush();
        $callerRoom->getRoom()->addRoomstatus($status);

        $crawler = $client->request('GET', '/api/v1/conferenceMapper?callerid=0098765455325&confid=12340');

        $res = (string) $client->getResponse()->getContent();
        $this->assertResponseIsSuccessful();

        self::assertEquals(
            json_encode([
                'state' => 'STARTED',
                'jwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgwIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMCIsImlzRTJFRUVuYWJsZWQiOmZhbHNlfSwidXNlciI6eyJuYW1lIjoiMDA5ODc2NTQ1NTMyNSIsImxhbmd1YWdlIjoiZGUiLCJ0aW1lem9uZSI6IkV1cm9wZS9CZXJsaW4ifX0sIm1vZGVyYXRvciI6ZmFsc2UsImxvYmJ5TW9kZXJhdG9yIjpmYWxzZSwidGhlbWUiOnsiY29sb3JTY2hlbWUiOiJsaWdodCJ9fQ.gkveKknwSQk98sYaB_f1CAbNvZ14kNMxdNNXI_Q0XsA',
                'room_name' => '123456780@testdomain.com',
                "display_name" => "0098765455325"
            ], JSON_THROW_ON_ERROR),
            $res
        );
    }
}
