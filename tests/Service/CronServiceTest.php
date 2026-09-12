<?php

namespace App\Tests\Service;

use App\Service\CronService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class CronServiceTest extends KernelTestCase
{
    public function testCheckCannotRunBecauseGetParameterIsUndefined(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $service = new CronService(
            $container->get(EntityManagerInterface::class),
            $container->get(FormFactoryInterface::class),
            $container->get(LoggerInterface::class)
        );

        $request = new Request(['token' => 'whatever'], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('getParameter');

        $service->check($request);
    }
}
