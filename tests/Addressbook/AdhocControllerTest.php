<?php

namespace App\Tests\Addressbook;

use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\OnlineStatus\PresenceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class AdhocControllerTest extends WebTestCase
{
    private function mockPresence(?bool $online): void
    {
        $presence = $this->createMock(PresenceService::class);
        $presence->method('isUserOnline')->willReturn($online);
        self::getContainer()->set(PresenceService::class, $presence);
    }

    private function mockPresenceNever(): void
    {
        $presence = $this->createMock(PresenceService::class);
        $presence->expects(self::never())->method('isUserOnline');
        self::getContainer()->set(PresenceService::class, $presence);
    }

    public function testcreateAdhocMeetingNoTag(): void
    {
        $client = static::createClient();


        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $crawler = $client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $data = $update->getData();
                $tmp = json_decode($data, true);
                if ($tmp['type'] === "call") {
                    self::assertStringContainsString('{"type":"call","title":"Ad Hoc Meeting"', $update->getData());
                    self::assertEquals('Ad Hoc Meeting', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                } elseif (str_contains($data, '"type":"notification"')) {
                    self::assertEquals('[Videokonferenz] Es gibt eine neue Einladung zur Videokonferenz Konferenz mit Test1, 1234, User, Test.', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);


        $this->mockPresence(true);
        $crawler = $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Konferenz mit Test1, 1234, User, Test'));
        self::assertEquals(
            json_encode(
                ['redirectUrl' => '/room/dashboard',
                    'popups' => [
                        [
                            'url' => 'http://localhost/room/join/b/' . $room->getId(),
                            'title' => 'Konferenz mit Test2, 1234, User2, Test2']
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );
        $crawler = $client->request('GET', json_decode($client->getResponse()->getContent(), true)['popups'][0]['url']);
        self::assertSelectorNotExists('#tagContent');

        $crawler = $client->request('GET', '/room/dashboard');

        self::assertEquals(1, $crawler->filter('h5:contains("Konferenz mit Test2, 1234, User2, Test2")')->count());
        self::assertEquals(0, $crawler->filter('h5:contains("Konferenz mit Test1, 1234, User, Test")')->count());
        $client->loginUser($user2);
        $crawler = $client->request('GET', '/room/dashboard');
        self::assertEquals(1, $crawler->filter('h5:contains("Konferenz mit Test1, 1234, User, Test")')->count());
        self::assertEquals(0, $crawler->filter('h5:contains("Konferenz mit Test2, 1234, User2, Test2")')->count());
    }

    public function testcreateAdhocMeetingWithTag(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $data = $update->getData();
                $tmp = json_decode($data, true);
                if ($tmp['type'] === "call") {
                    self::assertStringContainsString('{"type":"call","title":"Ad Hoc Meeting"', $update->getData());
                    self::assertEquals('Ad Hoc Meeting', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                } elseif (str_contains($data, '"type":"notification"')) {
                    self::assertEquals('[Videokonferenz] Es gibt eine neue Einladung zur Videokonferenz Konferenz mit Test1, 1234, User, Test.', $tmp['title']);
                    self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
                }
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);


        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        $this->mockPresence(true);
        $crawler = $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId() . '/' . $tag->getId());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'Konferenz mit Test1, 1234, User, Test'));

        self::assertEquals(
            json_encode(
                [
                    'redirectUrl' => '/room/dashboard',
                    'popups' => [
                        [
                            'url' => 'http://localhost/room/join/b/' . $room->getId(),
                            'title' => 'Konferenz mit Test2, 1234, User2, Test2']
                    ]
                ]
            ),
            $client->getResponse()->getContent()
        );
        $crawler = $client->request('GET', json_decode($client->getResponse()->getContent(), true)['popups'][0]['url']);
        self::assertSelectorTextContains('#tagContent', 'Test Tag Enabled');
        self::assertResponseIsSuccessful();
    }

    public function testcreateAdhocMeetingReceiverOffline(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user2->setOnlineStatus(0);
        $em->persist($user2);
        $em->flush();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $roomsBefore = $roomRepo->count([]);

        $this->mockPresenceNever();

        $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId());

        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayNotHasKey('popups', $response);
        self::assertEquals(
            'Der Teilnehmer ist offline oder nicht angemeldet. Der Anruf kann nicht gestartet werden.',
            $response['error']
        );
        self::assertEquals($roomsBefore, $roomRepo->count([]));
    }

    public function testcreateAdhocMeetingUsesDbStatusWhenPresenceUnknown(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $directSend = $this->getContainer()->get(DirectSendService::class);
        $directSend->setMercurePublisher(new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                return 'id';
            }
        ));

        $this->mockPresence(null);

        $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId());

        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('popups', $response);
    }

    public function testDeclineAdhocMeetingNotifiesCaller(): void
    {
        $client = static::createClient();
        // Keep the same kernel/container across the two requests so the Mercure mock set below
        // is still the publisher used when the callee declines.
        $client->disableReboot();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);

        $directSend = self::getContainer()->get(DirectSendService::class);
        $adhocCallFailed = null;
        $directSend->setMercurePublisher(new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use (&$adhocCallFailed): string {
                $data = json_decode($update->getData(), true);
                if (($data['type'] ?? null) === 'adhocCallFailed') {
                    $adhocCallFailed = ['topics' => $update->getTopics(), 'data' => $data];
                }
                return 'id';
            }
        ));

        // Start the ad-hoc call as the caller, which creates the room and waiting callout session.
        $client->loginUser($caller);
        $this->mockPresence(true);
        $client->request('GET', '/room/adhoc/meeting/' . $callee->getId() . '/' . $caller->getServers()[0]->getId());
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Konferenz mit Test1, 1234, User, Test']);
        self::assertNotNull($room);

        // The callee actively refuses the ringing call.
        $client->loginUser($callee);
        $client->request('GET', '/room/adhoc/decline/' . $room->getId());

        self::assertResponseIsSuccessful();
        self::assertEquals('DECLINED', json_decode($client->getResponse()->getContent(), true)['status']);
        self::assertNotNull($adhocCallFailed);
        self::assertEquals(['personal/' . $caller->getUid()], $adhocCallFailed['topics']);
        self::assertEquals('declined', $adhocCallFailed['data']['reason']);
        self::assertNull(
            self::getContainer()->get(CalloutSessionRepository::class)->findOneBy(['room' => $room, 'user' => $callee])
        );
    }

    public function testCancelAdhocMeetingStopsCalleeRinging(): void
    {
        $client = static::createClient();
        // Keep the same kernel/container across the two requests so the Mercure mock set below
        // is still the publisher used when the caller cancels.
        $client->disableReboot();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);

        $directSend = self::getContainer()->get(DirectSendService::class);
        $closeDialogTopics = [];
        $directSend->setMercurePublisher(new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use (&$closeDialogTopics): string {
                $data = json_decode($update->getData(), true);
                if (($data['type'] ?? null) === 'closeDialog') {
                    $closeDialogTopics = array_merge($closeDialogTopics, $update->getTopics());
                }
                return 'id';
            }
        ));

        // Start the ad-hoc call as the caller, which creates the room and waiting callout session.
        $client->loginUser($caller);
        $this->mockPresence(true);
        $client->request('GET', '/room/adhoc/meeting/' . $callee->getId() . '/' . $caller->getServers()[0]->getId());
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Konferenz mit Test1, 1234, User, Test']);
        self::assertNotNull($room);

        // The caller leaves/stops the attempt before the callee answers.
        $client->request('GET', '/room/adhoc/cancel');

        self::assertResponseIsSuccessful();
        self::assertEquals(1, json_decode($client->getResponse()->getContent(), true)['cancelled']);
        self::assertContains('personal/' . $callee->getUid(), $closeDialogTopics);
        self::assertNull(
            self::getContainer()->get(CalloutSessionRepository::class)->findOneBy(['room' => $room, 'user' => $callee])
        );
    }

    public function testTimeoutAdhocMeetingNotifiesCallerAndStopsRinging(): void
    {
        $client = static::createClient();
        // Keep the same kernel/container across the requests so the Mercure mock set below is
        // still the publisher used when the callee's browser reports the timeout.
        $client->disableReboot();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $caller = $userRepo->findOneBy(['email' => 'test@local.de']);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);

        $directSend = self::getContainer()->get(DirectSendService::class);
        $adhocCallFailed = null;
        $closeDialogTopics = [];
        $directSend->setMercurePublisher(new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use (&$adhocCallFailed, &$closeDialogTopics): string {
                $data = json_decode($update->getData(), true);
                if (($data['type'] ?? null) === 'adhocCallFailed') {
                    $adhocCallFailed = ['topics' => $update->getTopics(), 'data' => $data];
                } elseif (($data['type'] ?? null) === 'closeDialog') {
                    $closeDialogTopics = array_merge($closeDialogTopics, $update->getTopics());
                }
                return 'id';
            }
        ));

        // Start the ad-hoc call as the caller, which creates the room and the waiting callout session.
        $client->loginUser($caller);
        $this->mockPresence(true);
        $client->request('GET', '/room/adhoc/meeting/' . $callee->getId() . '/' . $caller->getServers()[0]->getId());
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Konferenz mit Test1, 1234, User, Test']);
        self::assertNotNull($room);

        // The callee's browser reaches the configured signaling duration without answering.
        $client->loginUser($callee);
        $client->request('GET', '/room/adhoc/timeout/' . $room->getId());

        self::assertResponseIsSuccessful();
        self::assertEquals('NO_ANSWER', json_decode($client->getResponse()->getContent(), true)['status']);
        // The caller is told that the call was not answered.
        self::assertNotNull($adhocCallFailed);
        self::assertEquals(['personal/' . $caller->getUid()], $adhocCallFailed['topics']);
        self::assertEquals('timeout', $adhocCallFailed['data']['reason']);
        // The callee's ringing dialog is closed on every device.
        self::assertContains('personal/' . $callee->getUid(), $closeDialogTopics);
        // The waiting session is gone, so the scheduled messenger timeout becomes a no-op.
        self::assertNull(
            self::getContainer()->get(CalloutSessionRepository::class)->findOneBy(['room' => $room, 'user' => $callee])
        );
    }

    public function testTimeoutAdhocMeetingWithoutWaitingSessionIsNoop(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $callee = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($callee);

        $directSend = self::getContainer()->get(DirectSendService::class);
        $directSend->setMercurePublisher(new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                return 'id';
            }
        ));

        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/adhoc/timeout/' . $room->getId());

        self::assertResponseIsSuccessful();
        self::assertEquals('ANSWERED', json_decode($client->getResponse()->getContent(), true)['status']);
    }

    public function testcreateAdhocMeetingReceiverOfflineByPresence(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);

        $this->mockPresence(false);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $roomsBefore = $roomRepo->count([]);

        $client->request('GET', '/room/adhoc/meeting/' . $user2->getId() . '/' . $user->getServers()[0]->getId());

        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayNotHasKey('popups', $response);
        self::assertEquals(
            'Der Teilnehmer ist offline oder nicht angemeldet. Der Anruf kann nicht gestartet werden.',
            $response['error']
        );
        self::assertEquals($roomsBefore, $roomRepo->count([]));
    }
}
