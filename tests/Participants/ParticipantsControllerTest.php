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

        $crawler = $client->request('GET', '/room/participant/add?room=' . $room->getId());
        $buttonCrawlerNode = $crawler->filter('#new_member_submit');

// retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $form['new_member[member]'] = "test@local4.de\ntestNeu@local.de";
        $form['new_member[moderator]'] = "test@australia.de\ntestNeuModerator@local.de";
        $client->submit($form);

        self::assertResponseRedirects('/room/dashboard');
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.snackbar', 'Die Teilnehmenden wurden eingeladen.');
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(7, $room->getUser()->count());
        $userRoomRepo = self::getContainer()->get(RoomsUserRepository::class);
        foreach ($room->getUser() as $data) {
            $roomUSer = $userRoomRepo->findOneBy(['room' => $room, 'user' => $data]);
            if ($data->getEmail() === 'test@australia.de') {
                self::assertTrue($roomUSer->getModerator());
            }
            if ($data->getEmail() === 'testNeuModerator@local.de') {
                self::assertTrue($roomUSer->getModerator());
            }
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

        $crawler = $client->request('GET', '/room/participant/add?room=' . $room->getId());
        $buttonCrawlerNode = $crawler->filter('#new_member_submit');

// retrieve the Form object for the form belonging to this button
        $form = $buttonCrawlerNode->form();
        $form['new_member[member]'] = "falschTeilnehmer";
        $form['new_member[moderator]'] = "falschModerator";
        $client->submit($form);

        self::assertResponseRedirects('/room/dashboard');
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.snackbar', 'Einige Teilnehmende wurden eingeladen. falschTeilnehmer, falschModerator ist/sind nicht korrekt und können nicht eingeladen werden.');
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
        $crawler = $client->request('GET', '/room/participant/add?room=' . $room->getId());
        self::assertResponseRedirects('/room/dashboard');
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.snackbar', 'Keine Berechtigung');
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
        self::assertSelectorTextContains('.snackbar', 'Die Einladung wurde erfolgreich versandt.');
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
