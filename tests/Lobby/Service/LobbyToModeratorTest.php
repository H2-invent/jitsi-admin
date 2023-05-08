<?php

namespace App\Tests\Lobby\Service;

use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class LobbyToModeratorTest extends KernelTestCase
{
    public function testSomething(): void
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
}
