<?php

namespace App\Tests\Controller;

use App\Entity\RoomStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventSyncApiControllerTest extends WebTestCase
{
    public function testIndexReportsRoomStartedForKnownUid(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $roomStatus = new RoomStatus();
        $roomStatus->setJitsiRoomId('eventsyncuid|meet.jit.si')
            ->setCreated(true)
            ->setUpdatedAt(new \DateTime())
            ->setRoomCreatedAt(new \DateTime())
            ->setCreatedAt(new \DateTime());
        $em->persist($roomStatus);
        $em->flush();

        $client->request('GET', '/api/v1/event/sync/?room_uid=eventsyncuid');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"status":"ROOM_STARTED"}', $client->getResponse()->getContent());
    }

    public function testIndexReportsRoomClosedForUnknownUid(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/event/sync/?room_uid=00000doesnotexist');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"status":"ROOM_CLOSED"}', $client->getResponse()->getContent());
    }

    public function testIndexDoesNotEnforceTheConfiguredToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/event/sync/?room_uid=00000doesnotexist');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"status":"ROOM_CLOSED"}', $client->getResponse()->getContent());
    }
}
