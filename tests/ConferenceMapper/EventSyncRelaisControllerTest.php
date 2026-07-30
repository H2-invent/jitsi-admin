<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Repository\RoomsRepository;
use App\Service\api\ConferenceMapperService;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EventSyncRelaisControllerTest extends WebTestCase
{
    public function testRoomOPened(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);

        $httpClientMock = $this->createStub(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createStub(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn(['status' => 'ROOM_STARTED']);

        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        // Erstelle das Service-Objekt mit dem HttpClientMock
        $conferenceMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $conferenceMapperService->setHttpClient($httpClientMock);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $callerRoom = new CallerRoom();
        $callerRoom->setRoom($room)
            ->setCallerId('555555')
            ->setCreatedAt(new \DateTime());
        $room->setUid('testUID1234');
        $room->getServer()->setJitsiEventSyncUrl('http://example.com')->setJigasiProsodyDomain('test.prosody.com');
        $manager->persist($room);
        $manager->persist($callerRoom);
        $manager->flush();


        $crawler = $client->request('GET', '/api/v1/conferenceMapper?confid=555555&callerid=12345678',);

        $this->assertResponseIsSuccessful();
        $result = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals('STARTED', $result['state']);
        self::assertEquals('testuid1234@test.prosody.com', $result['room_name']);
        self::assertEquals('User, Test, test@local.de', $result['display_name']);

        $decoded = JWT::decode($result['jwt'], new Key($room->getServer()->getAppSecret(), 'HS256'));
        self::assertEquals('jitsi_admin', $decoded->aud);
        self::assertEquals('jitsiId', $decoded->iss);
        self::assertEquals('meet.jit.si2', $decoded->sub);
        self::assertEquals('testuid1234', $decoded->room);
        self::assertEquals('Room Tomorrow', $decoded->context->room->name);
    }

    public function testRoomClosed(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);

        $httpClientMock = $this->createStub(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createStub(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn(['status' => 'ROOM_ClOSED']);

        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        // Erstelle das Service-Objekt mit dem HttpClientMock
        $conferenceMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $conferenceMapperService->setHttpClient($httpClientMock);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'Room Tomorrow']);
        $callerRoom = new CallerRoom();
        $callerRoom->setRoom($room)
            ->setCallerId('555555')
            ->setCreatedAt(new \DateTime());
        $room->getServer()->setJitsiEventSyncUrl('http://example.com');
        $manager->persist($room);
        $manager->persist($callerRoom);
        $manager->flush();


        $crawler = $client->request('GET', '/api/v1/conferenceMapper?confid=555555&callerid=123456',);

        $this->assertResponseIsSuccessful();
        self::assertEquals(
            '{"state":"WAITING","reason":"NOT_STARTED"}'
            , $client->getResponse()->getContent()
        );
    }

}
