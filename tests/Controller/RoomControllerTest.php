<?php

namespace App\Tests\Controller;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $room = $roomRepo->findOneBy(array('name'=>'No Right'));
        $client->request('GET', '/room/new?id='.$room->getId());
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
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $client->request('GET', '/room/new?id='.$room->getId());
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

        $client->request('GET', '/room/new?id=50n00000000');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

    }
}