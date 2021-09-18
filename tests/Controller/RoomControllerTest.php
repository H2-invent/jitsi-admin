<?php

namespace App\Tests\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
class RoomControllerTest extends WebTestCase
{

    public function testNew()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('testemanuel');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', '/room/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h5', 'Neue Konferenz erstellen');
    }
    public function testEditNoRight()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('testemanuel');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', '/room/new?id=1915');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }
    public function testEditRight()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('testemanuel');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', '/room/new?id=2012');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h5', 'Konferenz bearbeiten');

    }
    public function testnoRoom()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);

        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('testemanuel');

        // simulate $testUser being logged in
        $client->loginUser($testUser);

        $client->request('GET', '/room/new?id=5000');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }
}