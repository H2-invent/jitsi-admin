<?php

namespace App\Tests\Participants;

use App\Repository\RoomsRepository;
use App\Repository\RoomsUserRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ParticipantsControllerTest extends WebTestCase
{
    public function testCorrectInvite(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $organizer = $room->getModerator();
        $client->loginUser($organizer);

        $client->request(
            'POST',
            '/room/participant/add_bulk/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['member' => "test@local4.de\ntestNeu@local.de"], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($response['error']);
        self::assertStringContainsString('eingeladen', $response['message']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(5, $room->getUser()->count());
        $userRoomRepo = self::getContainer()->get(RoomsUserRepository::class);
        foreach ($room->getUser() as $data) {
            $roomUSer = $userRoomRepo->findOneBy(['room' => $room, 'user' => $data]);

            if ($data->getEmail() === 'test@local4.de') {
                self::assertNull($roomUSer);
            }
            if ($data->getEmail() === 'testNeu@local.de') {
                self::assertNull($roomUSer);
            }
        }
    }
    public function testWrongInvite(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $organizer = $room->getModerator();
        $client->loginUser($organizer);

        $client->request(
            'POST',
            '/room/participant/add_bulk/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['member' => 'falschTeilnehmer'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertFalse($response['error']);
        self::assertStringContainsString('falschTeilnehmer', $response['message']);

        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
    }
    public function testWrongInvitePermission(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getUser()[1];
        $client->loginUser($user);
        $client->request(
            'POST',
            '/room/participant/add_bulk/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['member' => 'falschTeilnehmer'], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(403);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
    }
    public function testParticipantPast(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getModerator();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/room/participant/past?room=' . $room->getId());
        self::assertEquals(3, $crawler->filter('li')->count());
    }
    public function testRemoveParticpantOther(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getModerator();
        $client->loginUser($user);
        $userTodelete = $room->getUser()[1];

        $crawler = $client->request('GET', '/room/participant/remove?room=' . $room->getId() . '&user=' . $userTodelete->getId());
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(2, $room->getUser()->count());
    }
    public function testRemoveParticpantOwn(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getUser()[1];
        $client->loginUser($user);
        $userTodelete = $room->getUser()[1];

        $crawler = $client->request('GET', '/room/participant/remove?room=' . $room->getId() . '&user=' . $userTodelete->getId());
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(2, $room->getUser()->count());
    }
    public function testRemoveParticpantNoPermission(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getUser()[2];
        $client->loginUser($user);
        $userTodelete = $room->getUser()[1];

        $crawler = $client->request('GET', '/room/participant/remove?room=' . $room->getId() . '&user=' . $userTodelete->getId());
        self::assertResponseRedirects('/room/dashboard');
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.snackbar', 'Keine Berechtigung');
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
    }
    public function testRemoveParticpantNonExistentUser(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $organizer = $room->getModerator();
        $client->loginUser($organizer);

        $client->request('GET', '/room/participant/remove?room=' . $room->getId() . '&user=999999999');
        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('error', $response);
        self::assertFalse($response['error']);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
    }
    public function testRemoveParticpantNonExistentUserNoPermission(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = $room->getUser()[2];
        $client->loginUser($user);

        $client->request('GET', '/room/participant/remove?room=' . $room->getId() . '&user=999999999');
        self::assertResponseRedirects('/room/dashboard');
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
    }
    public function testResendInvitation(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getUser()[0];
        $client->loginUser($user);

        $userToResend = $room->getUser()[1];

        $crawler = $client->request('GET', '/room/participant/resend?room=' . $room->getUidReal() . '&user=' . $userToResend->getId());
        self::assertResponseRedirects('/room/dashboard');
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.snackbar', 'Der Teilnehmer wurde erfolgreich eingeladen.');
    }
    public function testResendInvitationWrongPermission(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getUser()[2];
        $client->loginUser($user);

        $userToResend = $room->getUser()[1];

        $crawler = $client->request('GET', '/room/participant/resend?room=' . $room->getUidReal() . '&user=' . $userToResend->getId());
        self::assertResponseRedirects('/room/dashboard');
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.snackbar', 'Keine Berechtigung');
    }
    public function testResendInvitationWrongUser(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $user = $room->getUser()[0];
        $client->loginUser($user);
        $userrepo = self::getContainer()->get(UserRepository::class);

        $userToResend = $userrepo->findOneBy(['email' => 'test@australia.de']);

        $crawler = $client->request('GET', '/room/participant/resend?room=' . $room->getUidReal() . '&user=' . $userToResend->getId());
        self::assertResponseRedirects('/room/dashboard');
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.snackbar', 'Keine Berechtigung');
    }
}
