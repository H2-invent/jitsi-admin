<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateFastConfernceControllerTest extends WebTestCase
{
    public function testIndexCreatesFastConference(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/create/fast/confernce');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);
        $this->assertArrayHasKey('popups', $data);
        $this->assertNotEmpty($data['popups']);
    }

    public function testIndexRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/room/create/fast/confernce');

        $this->assertResponseRedirects();
    }
}
