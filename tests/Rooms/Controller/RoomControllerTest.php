<?php

namespace App\Tests\Rooms\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomControllerTest extends WebTestCase
{
    public function testNew()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', '/room/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h5', 'Bitte neue Konferenz erstellen');
    }


    public function testEditNoRight()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');

        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'No Right']);
        $client->request('GET', '/room/new?id=' . $room->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
    public function testEditRight()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');

        // simulate $testUser being logged in
        $client->loginUser($testUser);
        $roomRepo = static::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $client->request('GET', '/room/new?id=' . $room->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h5', 'Konferenz bearbeiten');
    }
    public function testnoRoom()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', '/room/new?id=-1');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }
}
