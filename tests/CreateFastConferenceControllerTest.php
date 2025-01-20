<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateFastConferenceControllerTest extends WebTestCase
{

    public function testCreateFastConference(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local.de']);
        $client->loginUser($user);
        $crawler = $client->request('get','/room/create/fast/confernce');
        $this->assertResponseIsSuccessful();
    }
}
