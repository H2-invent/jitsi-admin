<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContactApiControllerTest extends WebTestCase
{
    public function testIndexReturnsContactsServersAndTags(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/contact/api');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('contacts', $data);
        $this->assertArrayHasKey('servers', $data);
        $this->assertArrayHasKey('tags', $data);
        $this->assertNotEmpty($data['contacts']);
        $this->assertArrayHasKey('uid', $data['contacts'][0]);
        $this->assertArrayHasKey('id', $data['contacts'][0]);
    }

    public function testFixedRoomsReturnsPersistantRooms(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/fixed_rooms/api');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('rooms', $data);
        $this->assertNotEmpty($data['rooms']);
        $this->assertArrayHasKey('name', $data['rooms'][0]);
        $this->assertArrayHasKey('startUrl', $data['rooms'][0]);
        $this->assertArrayHasKey('moderator', $data['rooms'][0]);
    }

    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/room/contact/api');

        $this->assertResponseRedirects();
    }
}
