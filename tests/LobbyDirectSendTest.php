<?php

namespace App\Tests;

use App\Service\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class LobbyDirectSendTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $directSend->sendSnackbar('test/test/numberofUser','TestText','danger');

        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            var_dump($update->getTopics());
            $this->assertFalse($update->isPrivate());
            return 'id';
        });


            //$routerService = self::$container->get('router');
        //$myCustomService = self::$container->get(CustomService::class);
    }
}
