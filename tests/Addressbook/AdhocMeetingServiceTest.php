<?php

namespace App\Tests\Addressbook;

use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class AdhocMeetingServiceTest extends KernelTestCase
{
    public function testCreateAdhocmeeting(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
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

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room = $adhockservice->createAdhocMeeting($user, $user2, $user->getServers()[0]);
        self::assertEquals('Konferenz mit Test1, 1234, User, Test', $room->getName());
        self::assertEquals('Konferenz mit Test2, 1234, User2, Test2', $room->getSecondaryName());
        self::assertNull($room->getTag());
    }

    public function testCreateAdhocmeetingWithTag(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
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

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(['title' => 'Test Tag Enabled']);
        $room = $adhockservice->createAdhocMeeting($user, $user2, $user->getServers()[0], $tag);
        self::assertEquals($tag->getTitle(), $room->getTag()->getTitle());
        self::assertEquals($tag, $room->getTag());
    }
}
