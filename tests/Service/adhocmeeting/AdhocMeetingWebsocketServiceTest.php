<?php

namespace App\Tests\Service\adhocmeeting;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\AdHocMeeting\AdhocMeetingWebsocketService;
use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class AdhocMeetingWebsocketServiceTest extends KernelTestCase
{
    /** @var Update[] */
    private array $updates = [];

    public function testSendAdhocMeetingWebsocketPublishesDialogPushAndSound(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $receiver = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $creator = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = $container->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);

        $this->updates = [];
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $this->updates[] = $update;
                return 'id';
            }
        );
        $container->get(DirectSendService::class)->setMercurePublisher($hub);

        $service = $container->get(AdhocMeetingWebsocketService::class);
        $service->sendAddhocMeetingWebsocket($receiver, $creator, $room);

        $expectedTopic = 'personal/' . $receiver->getUid();
        $expectedText = 'Test1, 1234, User, Test möchte mit Ihnen eine Ad-Hoc Videokonferenz erstellen';

        self::assertCount(3, $this->updates);
        foreach ($this->updates as $update) {
            self::assertSame([$expectedTopic], $update->getTopics());
        }

        $dialog = json_decode($this->updates[0]->getData(), true);
        self::assertSame('dialog', $dialog['type']);
        self::assertSame('Ad Hoc Meeting', $dialog['header']);
        self::assertSame($expectedText, $dialog['text']);
        self::assertSame('question', $dialog['dialogType']);
        self::assertSame('btn btn-success startIframe', $dialog['buttons'][0]['class']);
        self::assertSame('/room/join/b/' . $room->getId(), $dialog['buttons'][0]['link']);
        self::assertSame('TestMeeting: 1', $dialog['buttons'][0]['data']['roomname']);
        self::assertSame('btn btn-danger ', $dialog['buttons'][1]['class']);

        $push = json_decode($this->updates[1]->getData(), true);
        self::assertSame('browserPush', $push['type']);
        self::assertSame('Ad Hoc Meeting', $push['title']);
        self::assertSame($expectedText, $push['pushNotification']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $push['messageId']);

        $sound = json_decode($this->updates[2]->getData(), true);
        self::assertSame('playSound', $sound['type']);
        self::assertSame('caller', $sound['soundName']);
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $sound['messageId']);
    }
}
