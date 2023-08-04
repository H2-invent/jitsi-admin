<?php

namespace App\Tests\Lobby\Service;

use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

class LobbyDirectSendTest extends KernelTestCase
{
    public function testSnackbar(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"snackbar","message":"TestText","color":"danger"}', $update->getData());
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendSnackbar('test/test/numberofUser', 'TestText', 'danger');
    }

    public function testBrowserNotification(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"notification","title":"Title of Browser Notification","message":"I`m the message which is in the body part","pushNotification":"I`m the message in the pushnotification from the OS","messageId":"' . md5('Title of Browser Notification' . 'I`m the message which is in the body part') . '","color":"success"}', $update->getData());
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $id = md5('Title of Browser Notification' . 'I`m the message which is in the body part');
        $directSend->sendBrowserNotification('test/test/numberofUser', 'Title of Browser Notification', 'I`m the message which is in the body part', 'I`m the message in the pushnotification from the OS', $id, 'success');
    }

    public function testRedirectResponse(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"redirect","url":"\/rooms\/testMe","timeout":1000}', $update->getData());
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendRedirect('test/test/numberofUser', '/rooms/testMe', 1000);
    }

    public function testRefreshResponse(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"refresh","reloadUrl":"\/rooms\/testMe #testId"}', $update->getData());
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendRefresh('test/test/numberofUser', '/rooms/testMe #testId');
    }

    public function testsendEndMeeting(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"endMeeting","url":"\/room\/dashboard","timeout":5000}', $update->getData());
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendEndMeeting('test/test/numberofUser', '/room/dashboard', 5000);
    }

    public function testsendEndMeetingDefault(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"endMeeting","url":"\/room\/dashboard","timeout":1000}', $update->getData());
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendEndMeeting('test/test/numberofUser', '/room/dashboard');
    }

    public function testsetSendnewCal(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $tmp = json_decode($update->getData(), true);
                self::assertEquals($tmp['title'], 'Neuer Anruf');
                self::assertEquals($tmp['message'], 'Sie haben einen nuene Anruf');
                self::assertEquals($tmp['pushMessage'], 'Das kommt in die Push');
                self::assertEquals($tmp['time'], 30000);
                self::assertEquals($tmp['messageId'], '0x01');
                self::assertEquals(['test/test/newCall'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendCallAdhockmeeding('Neuer Anruf', 'test/test/newCall', 'Sie haben einen nuene Anruf', 'Das kommt in die Push', 30000, '0x01');
    }
    public function testsendRefreshDashboard(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $tmp = json_decode($update->getData(), true);
                self::assertEquals($tmp['type'], 'refreshDashboard');
                self::assertEquals(['test/test/newCall'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendRefreshDashboard('test/test/newCall');
    }
    public function testModal(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $twig = self::getContainer()->get(Environment::class);
        $content = $twig->render('lobby_participants/choose.html.twig', ['appUrl' => 'https://test.de/app', 'browserUrl' => 'https://test.de/browser']);

        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals(
                    '{"type":"modal","content":"<div class=\"modal-dialog modal-dialog-centered\">\n    <div class=\"modal-content\">\n        <div class=\"modal-header  light-blue darken-3 white-text\">\n            <h5 class=\"modal-title\">Der Moderator hat Sie zur Konferenz hinzugef\u00fcgt<\/h5>\n             <button type=\"button\" class=\"btn-close\" data-mdb-dismiss=\"modal\" aria-label=\"Close\"><\/button>\n        <\/div>\n        <div class=\"modal-body\">\n           <p>Sie haben die Wahl ob Sie mit dem Browser oder der Jitsi-Meet Electro App dem Meeting beitreten wollen. Sind Sie sich unsicher, w\u00e4hlen Sie &quot;Im Browser&quot;.<\/p>\n        <\/div>\n        <div class=\"btn-group\">\n            <a href=\"https:\/\/test.de\/browser\" class=\"btn btn-outline-primary\">Starten<\/a>\n            <a href=\"https:\/\/test.de\/app\" class=\"btn btn-outline-primary\">In der App<\/a>\n        <\/div>\n\n    <\/div>\n<\/div>"}',
                    $update->getData()
                );
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);
        $directSend->sendModal('test/test/numberofUser', $content);
    }

    public function testCloseNotification(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $twig = self::getContainer()->get(Environment::class);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                self::assertEquals('{"type":"cleanNotification","messageId":"testClose123"}', $update->getData());
                self::assertEquals(['test/test/numberofUser'], $update->getTopics());
                return 'id';
            }
        );

        $directSend->setMercurePublisher($hub);
        $directSend->sendCleanBrowserNotification('test/test/numberofUser', 'testClose123');
    }
}
