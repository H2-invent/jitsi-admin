<?php

namespace App\Tests\SipCaller;

use App\Repository\RoomsRepository;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SipCallerLobbyControllerTest extends WebTestCase
{
    public function testCallerLobbyNotVerified(): void
    {
        $client = static::createClient();


        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');

        $user = $room->getModerator();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/lobby/moderator/b/'.$room->getUidReal());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Lobby für die Konferenz: '.$room->getName());
        $this->assertSelectorTextContains('.participantsName', $session->getLobbyWaitingUser()->getShowName());
        $this->assertSelectorTextContains('.callerId', $session->getCallerId());
        $this->assertSelectorNotExists('.callerVerified');
    }
    public function testCallerLobbyVerified(): void
    {
        $client = static::createClient();


        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 19'));
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '0123456789');

        $user = $room->getModerator();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/lobby/moderator/b/'.$room->getUidReal());
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h3', 'Lobby für die Konferenz: '.$room->getName());
        $this->assertSelectorTextContains('.participantsName', $session->getLobbyWaitingUser()->getShowName());
        $this->assertSelectorTextContains('.callerId', $session->getCallerId());
        $this->assertSelectorExists('.callerVerified');
    }
}
