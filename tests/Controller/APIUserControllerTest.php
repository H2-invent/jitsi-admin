<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class APIUserControllerTest extends WebTestCase
{
    public function testIndexReturnsRoomsForLoggedInUser(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/api/v1/getAllEntries');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('title', $data[0]);
        $this->assertArrayHasKey('start', $data[0]);
        $this->assertArrayHasKey('end', $data[0]);
        $this->assertArrayHasKey('allDay', $data[0]);
    }

    public function testGetRoomInformations(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);

        $client->request('GET', '/api/v1/info/' . $room->getUidReal());

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testAddUserToRoomWithValidApiKey(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);

        $client->request('POST', '/api/v1/user', [
            'uid' => $room->getUidReal(),
            'email' => 'test@local4.de',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $room->getServer()->getApiKey()]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame('Teilnehmer test@local4.de erfolgreich hinzugefügt', $data['text']);
    }

    public function testAddUserToRoomWithInvalidApiKey(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);

        $client->request('POST', '/api/v1/user', [
            'uid' => $room->getUidReal(),
            'email' => 'test@local4.de',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer wrong']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('No Server found', $data['text']);
    }

    public function testRemoveUserFromRoomWithValidApiKey(): void
    {
        $client = static::createClient();
        $container = self::getContainer();
        $room = $container->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room->addUser($user);
        $container->get(EntityManagerInterface::class)->flush();

        $client->request('DELETE', '/api/v1/user', [
            'uid' => $room->getUidReal(),
            'email' => 'test@local4.de',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $room->getServer()->getApiKey()]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame('Teilnehmer test@local4.de erfolgreich gelöscht', $data['text']);
        $this->assertNotContains($user, $room->getUser()->toArray());
    }

    public function testRemoveUserFromRoomWithInvalidApiKey(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '9876543210']);

        $client->request('DELETE', '/api/v1/user', [
            'uid' => $room->getUidReal(),
            'email' => 'test@local4.de',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer wrong']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('No Server found', $data['text']);
    }
}
