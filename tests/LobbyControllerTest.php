<?php

namespace App\Tests;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LobbyControllerTest extends WebTestCase
{
    public function testPressStart(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(array('email' => 'test@local.de'));
        $client->loginUser($testUser);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $crawler = $client->request('GET', '/room/join/b/'.$room->getId());
        $this->assertTrue($client->getResponse()->isRedirect($url->generate('lobby_moderator',array('uid'=>$room->getUid()))));
    }
    public function testlobbyStartModerator(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $url = self::getContainer()->get(UrlGeneratorInterface::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        // retrieve the test user
        $testUser = $userRepository->findOneBy(array('email' => 'test@local.de'));
        $client->loginUser($testUser);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $crawler = $client->request('GET', $url->generate('lobby_moderator',array('uid'=>$room->getUid())));
        $this->assertSelectorTextContains('h1','Lobby für die Konferenz: This is a fixed room',);
    }
}
