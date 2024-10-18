<?php

namespace App\Tests\JitsiComponentSelector;

use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Entity\RoomStatus;
use App\Repository\CallerSessionRepository;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Service\caller\CallerLeftService;
use App\Service\caller\CallerPinService;
use App\Service\caller\CallerPrepareService;
use App\Service\caller\CallerSessionService;
use App\Service\caller\JitsiComponentSelectorService;
use App\Service\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class CallerControllerSipVideoTest extends WebTestCase
{

    public function testGetCallerPinWithSipVideo(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);


        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '123419';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[0];

        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234','is_video'=>true]);
        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 19']);
        $caller = $room->getCallerIds()[0];
        $session = $caller->getCallerSession();
        self::assertTrue($session->isIsSipVideoUser());
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                [
                    'auth_ok' => true,
                    'links' => [
                        'session' => '/api/v1/lobby/sip/session?session_id=' . $session->getSessionId(),
                        'left' => '/api/v1/lobby/sip/session/left?session_id=' . $session->getSessionId()
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );
    }



    public function testAcceptAllCaller(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);



        $res = $this->startWorkflow($client);
        $sessionLink = $res[0];
        $leafLink = $res[1];
        $roomService = self::getContainer()->get(RoomService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);


        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $status = new RoomStatus();
        $status->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setJitsiRoomId('test')
            ->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());
        $manager->persist($status);
        $manager->flush();



        $client->loginUser($room->getModerator());
        $lobbyWaitinguser = $this->getLobbyWaitinguser($sessionLink);
        $crawler = $client->request('GET', '/room/lobby/acceptAll/' . $lobbyWaitinguser->getRoom()->getUidReal());

        $httpClientMock = $this->createMock(HttpClientInterface::class);
        // Beispiel Response
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('toArray')->willReturn([
            "sessionId" => "4a258446-70ff-4096-b122-da904d3bc591",
            "type" => "SIP-JIBRI",
            "environment" => "default-env",
            "region" => "default-region",
            "status" => "PENDING",
            "componentKey" => "h2invent-sip-test-controller",
            "metadata" => [
                "sipUsername" => null
            ]
        ]);
        $responseMock->method('getStatusCode')->willReturn(200);
        // Konfiguriere den HttpClientMock, um die Response zurückzugeben
        $httpClientMock->method('request')->willReturn($responseMock);
        $jitsiComponentSelectorService = self::getContainer()->get(JitsiComponentSelectorService::class);

        $jitsiComponentSelectorService->setHttpClient($httpClientMock);


        $crawler = $client->request('GET', $sessionLink);
        $session = $this->getSessionfromLink($sessionLink);


        $this->assertResponseIsSuccessful();
    }


    function startWorkflow(KernelBrowser $client)
    {

        $callerLEftService = self::getContainer()->get(CallerLeftService::class);
        $sessionService = self::getContainer()->get(CallerSessionService::class);
        $callerPinService = self::getContainer()->get(CallerPinService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $callerPrepareService = self::getContainer()->get(CallerPrepareService::class);
        $id = '12340';
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setLobby(true);
        $callerPrepareService->createUserCallerIDforRoom($room);
        $caller = $room->getCallerIds()[1];
        //enter the room and check if the room is okay
        $crawler = $client->request('GET', '/api/v1/lobby/sip/room/' . $id);
        $this->assertResponseIsSuccessful();

        //enter the users pin
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234','is_video'=>true]);
        $this->assertResponseIsSuccessful();
        $sessionLink = json_decode($client->getResponse()->getContent(), true)['links']['session'];
        $leafLink = json_decode($client->getResponse()->getContent(), true)['links']['left'];

        //try entering again. the user should not be access again
        $crawler = $client->request('POST', '/api/v1/lobby/sip/pin/' . $id, ['pin' => $caller->getCallerId(), 'caller_id' => '1234']);
        $this->assertJsonStringEqualsJsonString(json_encode(['auth_ok' => false, 'links' => []]), $client->getResponse()->getContent());
        $this->assertResponseIsSuccessful();

        $crawler = $client->request('GET', $sessionLink);
        self::assertEquals(
            [
                'status' => "WAITING",
                "reason" => "NOT_ACCEPTED",
                "number_of_participants" => 0,
                "status_of_meeting" => "NOT_STARTED",
                "message" => [],
                'links' => [
                    'session' => $sessionLink,
                    'left' => $leafLink,
                ]
            ],
            json_decode($client->getResponse()->getContent(), true)
        );

        $this->assertResponseIsSuccessful();
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        return [$sessionLink, $leafLink];
    }
    function getLobbyWaitinguser($link): ?LobbyWaitungUser
    {
        $sessionId = explode('=', $link);
        $sessionId = $sessionId[sizeof($sessionId) - 1];
        $sessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $session = $sessionRepo->findOneBy(['sessionId' => $sessionId]);
        $lobbyUserRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyUser = $lobbyUserRepo->findOneBy(['uid' => $session->getLobbyWaitingUser()->getUid()]);
        $lobbyUser->setCallerSession($session);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($lobbyUser);
        $manager->flush();
        return $lobbyUser;
    }

    function getSessionfromLink($link): ?CallerSession
    {
        $sessionId = explode('=', $link);
        $sessionId = $sessionId[sizeof($sessionId) - 1];
        $sessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $session = $sessionRepo->findOneBy(['sessionId' => $sessionId]);
        return $session;
    }

}
