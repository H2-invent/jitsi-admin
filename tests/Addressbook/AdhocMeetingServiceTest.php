<?php

namespace App\Tests\Addressbook;

use App\Repository\UserRepository;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class AdhocMeetingServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $adhockservice = self::getContainer()->get(AdhocMeetingService::class);
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            self::assertStringContainsString('{"type":"call","title":"Ad Hoc Meeting"', $update->getData());
            self::assertEquals(['personal/kljlsdkjflkjddfgslfjsdlkjsdflkj'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local.de'));
        $user2 = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $adhockservice->createAdhocMeeting($user,$user2,$user->getServers()[0]);


        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
}
