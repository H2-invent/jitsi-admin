<?php

namespace App\Tests;

use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InviteUserOpenRoomsButtonTest extends WebTestCase
{

    public function test_NoStart_isModerator_hasLobby(): void
    {
        $client = static::createClient();
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);
        $room = $this->getRoomByName('This Room has no participants and fixed room and Lobby activated');
        $crawler = $client->request('GET', '/myRoom/start/' . $room->getUid());

        $buttonCrawlerNode = $crawler->selectButton('Beitreten');
        $form = $buttonCrawlerNode->form();
        $form['join_my_room[name]'] = 'Test User 123';
        $client->submit($form);
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#inviteButtonOpenRoom');

    }



    public function getRoomByName($name)
    {
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => $name]);
        return $room;
    }

    public function getUSerByEmail($name)
    {
        $userRepo = $this->getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => $name]);
        return $user;
    }
}
