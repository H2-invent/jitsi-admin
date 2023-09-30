<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\Server;
use App\Repository\CallerRoomRepository;
use App\Service\api\ConferenceMapperService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConferenceMapperCallerExternalStatus extends KernelTestCase
{
    public function testFindRoomStatusFromOtherServer()
    {
        // Mock für den HttpClient
        $httpClientMock = $this->createMock(HttpClientInterface::class);

        // Beispiel Room und Token
        $room = new Rooms();
        $room->setServer(new Server());
        $room->getServer()->setJitsiEventSyncUrl('http://example.com');
        $room->setUid('example_uid');
        $token = 'example_token';

        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn(['status' => 'ROOM_STARTED']);

        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        // Erstelle das Service-Objekt mit dem HttpClientMock
        $conferenceMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $conferenceMapperService->setHttpClient($httpClientMock);

        // Aufruf der Funktion
        $result = $conferenceMapperService->findRoomStatusFromOtherServer($room, $token);

        // Erwartung: Der Status ist 'ROOM_STARTED', also sollte das Ergebnis true sein
        $this->assertTrue($result);
    }



    public function testFindRoomStatusFromOtherServerCLosed()
    {
        // Mock für den HttpClient
        $httpClientMock = $this->createMock(HttpClientInterface::class);

        // Beispiel Room und Token
        $room = new Rooms();
        $room->setServer(new Server());
        $room->getServer()->setJitsiEventSyncUrl('http://example.com');
        $room->setUid('example_uid');
        $token = 'example_token';

        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn(['status' => 'ROOM_CLOSED']);

        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);

        // Erstelle das Service-Objekt mit dem HttpClientMock
        $conferenceMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $conferenceMapperService->setHttpClient($httpClientMock);

        // Aufruf der Funktion
        $result = $conferenceMapperService->findRoomStatusFromOtherServer($room, $token);

        $this->assertFalse($result);
    }




}
