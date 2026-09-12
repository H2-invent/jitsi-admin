<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangePermissionsControllerTest extends WebTestCase
{
    private function getRoomAndUsers(): array
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $participant = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        return [$moderator, $participant, $room];
    }

    public function testShareScreenTogglesPermission(): void
    {
        $client = static::createClient();
        [$moderator, $participant, $room] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/change/permissions/shareScreen?room=' . $room->getId() . '&user=' . $participant->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
    }

    public function testShareScreenWithoutRoomRedirects(): void
    {
        $client = static::createClient();
        [$moderator, $participant] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/change/permissions/shareScreen?room=999999&user=' . $participant->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testPrivateMessageTogglesPermission(): void
    {
        $client = static::createClient();
        [$moderator, $participant, $room] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/change/permissions/privateMessage?room=' . $room->getId() . '&user=' . $participant->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
    }

    public function testPrivateMessageWithoutUserRedirects(): void
    {
        $client = static::createClient();
        [$moderator, , $room] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/change/permissions/privateMessage?room=' . $room->getId() . '&user=999999');

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testRoomTransferModerator(): void
    {
        $client = static::createClient();
        [$moderator, $participant, $room] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/addModerator?room=' . $room->getId() . '&user=' . $participant->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
    }

    public function testRoomTransferModeratorWithoutRoomRedirects(): void
    {
        $client = static::createClient();
        [$moderator, $participant] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/addModerator?room=999999&user=' . $participant->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testRoomTransferLobbyModerator(): void
    {
        $client = static::createClient();
        [$moderator, $participant, $room] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/change/lobbyModerator?room=' . $room->getId() . '&user=' . $participant->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
    }

    public function testRoomTransferLobbyModeratorWithoutRoom(): void
    {
        $client = static::createClient();
        [$moderator] = $this->getRoomAndUsers();
        $client->loginUser($moderator);

        $client->request('GET', '/room/change/lobbyModerator?room=999999&user=999999');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('snack', $data);
    }
}
