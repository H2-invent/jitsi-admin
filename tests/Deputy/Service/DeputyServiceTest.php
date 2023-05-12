<?php

namespace App\Tests\Deputy\Service;

use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use App\Repository\UserRepository;
use App\Service\Deputy\DeputyService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DeputyServiceTest extends KernelTestCase
{
use RefreshDatabaseTrait;
    public function testSetDeputy(): void
    {
        $kernel = self::bootKernel();
        $service = self::getContainer()->get(DeputyService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        self::assertEquals(DeputyService::$IS_DEPUTY, $service->setDeputy($master, $deputy));
        self::assertEquals(DeputyService::$IS_NOT_DEPUTY, $service->removeDeputy($master, $deputy));
    }
    public function testToggleDeputy(): void
    {
        $kernel = self::bootKernel();
        $service = self::getContainer()->get(DeputyService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $master = $userRepo->findOneBy(['email' => 'test@local.de']);
        $deputy = $userRepo->findOneBy(['email' => 'test@local2.de']);
        self::assertEquals(DeputyService::$IS_DEPUTY, $service->toggleDeputy($master, $deputy));
        self::assertEquals(DeputyService::$IS_NOT_DEPUTY, $service->toggleDeputy($master, $deputy));
        self::assertEquals(DeputyService::$IS_DEPUTY, $service->toggleDeputy($master, $deputy));
    }
}
