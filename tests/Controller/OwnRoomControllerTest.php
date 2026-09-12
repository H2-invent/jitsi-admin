<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OwnRoomControllerTest extends WebTestCase
{
    public function testIndexRendersJoinPageForOpenRoom(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uid' => '561d6f51s6f']);

        $client->request('GET', '/myRoom/start/' . $room->getUid());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', $room->getName());
    }

    public function testIndexUnknownRoomRedirectsToJoinPage(): void
    {
        $client = static::createClient();

        $client->request('GET', '/myRoom/start/does-not-exist');

        $this->assertResponseRedirects('/join');
    }

    public function testWaitingRendersWaitingPageWhileRoomIsClosed(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uid' => 'roomTomorrow']);

        $client->request('GET', '/mywaiting/waiting?uid=' . $room->getUid() . '&name=TestUser&type=b');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $room->getName());
    }

    public function testWaitingEntersRunningRoom(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uid' => 'runningRoomNow']);

        $client->request('GET', '/mywaiting/waiting?uid=' . $room->getUid() . '&name=TestUser&type=b');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($room->getName(), $client->getResponse()->getContent());
        $this->assertStringContainsString("displayName: 'TestUser'", $client->getResponse()->getContent());
    }

    public function testCheckWaitingReportsRoomClosed(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uid' => 'roomTomorrow']);

        $client->request('GET', '/mywaiting/check/' . $room->getUid() . '/TestUser/b');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString('{"error":true}', $client->getResponse()->getContent());
    }

    public function testCheckWaitingReturnsRedirectUrlForRunningRoom(): void
    {
        $client = static::createClient();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uid' => 'runningRoomNow']);

        $client->request('GET', '/mywaiting/check/' . $room->getUid() . '/TestUser/b');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertArrayHasKey('url', $data);
        $this->assertStringContainsString($room->getUid(), $data['url']);
        $this->assertStringContainsString('TestUser', $data['url']);
    }

    public function testLinkRendersForModerator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uid' => 'runningRoomNow']);
        $client->loginUser($user);

        $client->request('GET', '/room/enterLink/' . $room->getUid());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.modal-dialog');
    }

    public function testLinkRejectsForeignUser(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uid' => 'runningRoomNow']);
        $client->loginUser($user);

        $client->request('GET', '/room/enterLink/' . $room->getUid());

        $this->assertResponseStatusCodeSame(404);
    }
}
