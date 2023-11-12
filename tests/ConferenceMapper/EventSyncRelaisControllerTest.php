<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Repository\RoomsRepository;
use App\Service\api\ConferenceMapperService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EventSyncRelaisControllerTest extends WebTestCase
{
    public function testRoomOPened(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);

        $httpClientMock = $this->createMock(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
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
        self::assertEquals(
            '{"state":"STARTED","jwt":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoidGVzdHVpZDEyMzQiLCJjb250ZXh0Ijp7InVzZXIiOnsibmFtZSI6IlVzZXIsIFRlc3QsIHRlc3RAbG9jYWwuZGUifX0sIm1vZGVyYXRvciI6ZmFsc2V9.wwuEkSrJwS86IEi-3QIXV30StotROOmgLYS1nU3IjuY","room_name":"testuid1234@test.prosody.com"}'
            , $client->getResponse()->getContent()
        );

    }

    public function testRoomClosed(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);

        $httpClientMock = $this->createMock(HttpClientInterface::class);


        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
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
