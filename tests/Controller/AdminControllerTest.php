<?php

namespace App\Tests\Controller;

use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminControllerTest extends WebTestCase
{
    public function testServerRendersChartModal(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/admin/server/' . $server->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testServerRejectsNotAssignedServer(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $client->loginUser($user);

        $client->request('GET', '/admin/server/' . $server->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }
}
