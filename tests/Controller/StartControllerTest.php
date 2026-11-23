<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StartControllerTest extends WebTestCase
{
    public function testJoinRoomRedirectsToMeeting(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/join/a/' . $room->getId());

        $this->assertTrue($client->getResponse()->isRedirect());
        $location = $client->getResponse()->headers->get('Location');
        $this->assertStringStartsWith('jitsi-meet://', $location);
        $this->assertStringNotContainsString('/room/dashboard', $location);
    }

    public function testCheckCorsRoomRendersPage(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/checkCors?url=meet.example.com&cors=1');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('meet.example.com', $client->getResponse()->getContent());
    }
}
