<?php

namespace App\Tests\JitsiEvents\controller;

use App\Entity\RoomStatus;
use App\Repository\RoomStatusRepository;
use App\Service\api\EventSyncApiService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JitsiEventSyncApiControllerTest extends WebTestCase
{
    public function testRoomClosed(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer 123456']);
        $crawler = $client->request('POST', '/api/v1/event/sync/?room_uid=00000',);
        $this->assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"ROOOM_CLOSED"}',$client->getResponse()->getContent());
    }
    public function testRoomStarted(): void
    {
        $client = static::createClient([],['HTTP_AUTHORIZATION' => 'Bearer 123456']);
        $roomStatusRepositoryMock = $this->getMockBuilder(RoomStatusRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $roomStatusRepositoryMock->method('findRoomStatusByUid')
            ->willReturn(new RoomStatus());

        $eventSyncApiService = new EventSyncApiService($roomStatusRepositoryMock);
        self::getContainer()->set(EventSyncApiService::class,$eventSyncApiService);
        $crawler = $client->request('POST', '/api/v1/event/sync/?room_uid=123456');
        $this->assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"ROOM_STARTED"}',$client->getResponse()->getContent());
    }

}
