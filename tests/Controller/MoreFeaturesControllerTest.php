<?php

namespace App\Tests\Controller;

use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MoreFeaturesControllerTest extends WebTestCase
{
    public function testIndexReturnsFeatureFlag(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/room/features/more?id=' . $server->getId());

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('feature', $data);
        $this->assertArrayHasKey('enableFeateureJwt', $data['feature']);
    }
}
