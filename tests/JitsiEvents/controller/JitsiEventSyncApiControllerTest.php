<?php

namespace App\Tests\JitsiEvents\controller;

use App\Entity\RoomStatus;
use App\Repository\RoomStatusRepository;
use App\Service\api\EventSyncApiService;
use Doctrine\ORM\EntityManagerInterface;
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
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomsStatus = new RoomStatus();
        $roomsStatus->setJitsiRoomId('123456|meet.jit.si')
            ->setCreated(true)
            ->setUpdatedAt(new \DateTime())
            ->setRoomCreatedAt(new \DateTime())
        ->setCreatedAt(new \DateTime());
        $manager->persist($roomsStatus);
        $manager->flush();

        $crawler = $client->request('POST', '/api/v1/event/sync/?room_uid=123456');
        $this->assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"status":"ROOM_STARTED"}',$client->getResponse()->getContent());
    }

}
