<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ParticipantControllerTest extends WebTestCase
{
    public function testIndexSearchesParticipants(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/participant/search?search=test');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('group', $data);
    }

    public function testRoomAddUserRendersModal(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/participant/add/' . $room->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testRoomAddUserSubmitsParticipants(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/participant/add/' . $room->getId());
        $form = $crawler->filter('form')->form();
        $form['new_member[member]'] = 'test@local4.de';
        $client->submit($form);

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testRoomAddUserRejectsOtherUser(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/participant/add/' . $room->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testRoomAddUserSingleAddsParticipant(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/participant/add_single/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['participant' => ['test@local4.de']])
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('validMember', $data);
        $this->assertContains('test@local4.de', $data['validMember']);
    }

    public function testRoomAddUserSingleRejectsWithoutParticipantField(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/participant/add_single/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['something' => 'else'])
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testRoomPastUserRendersModal(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/participant/past?room=' . $room->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testRoomUserRemoveRemovesUser(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $participant = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($moderator);

        $client->request('GET', '/room/participant/remove?room=' . $room->getId() . '&user=' . $participant->getId());

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertSame('Teilnehmende gelöscht', $data['message']);
    }

    public function testRoomUserRemoveRejectsUnauthorizedUser(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $participant = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $outsider = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($outsider);

        $client->request('GET', '/room/participant/remove?room=' . $room->getId() . '&user=' . $participant->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testRoomUserResendSendsInvitation(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $participant = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($moderator);

        $client->request('GET', '/room/participant/resend?room=' . $room->getUidReal() . '&user=' . $participant->getId());

        $this->assertResponseRedirects('/room/dashboard');
    }
}
