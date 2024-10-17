<?php

namespace App\Tests\SipCaller;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
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

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($moderator);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);

        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';

        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '012345');


        $crawler = $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.joinPageHeader', $room->getName());
        $this->assertSelectorTextContains('.participantsName', $session->getLobbyWaitingUser()->getShowName());
        $this->assertSelectorTextContains('.callerId', $session->getCallerId());
        $this->assertSelectorNotExists('.callerVerified');
        $this->assertSelectorExists('.callerNotVerified');
    }
    public function testCallerLobbyVerified(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($moderator);

        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $roomService = self::getContainer()->get(RoomService::class);

        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';

        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];
        $session = $callerPinService->createNewCallerSession($id, $caller->getCallerId(), '0123456789');


        $crawler = $client->request('GET', '/room/lobby/moderator/b/' . $room->getUidReal());
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('.joinPageHeader', $room->getName());
        $this->assertSelectorTextContains('.participantsName', $session->getLobbyWaitingUser()->getShowName());
        $this->assertSelectorTextContains('.callerId', $session->getCallerId());
        $this->assertSelectorExists('.callerVerified');
    }
}
