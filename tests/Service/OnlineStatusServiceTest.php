<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\OnlineStatusService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OnlineStatusServiceTest extends KernelTestCase
{
    public function testGetUserStatusReturnsConfiguredDefaultWhenNotSet(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(OnlineStatusService::class);
        $default = (int) self::getContainer()->getParameter('LAF_DEFAULT_ONLINE_STATUS');

        self::assertSame($default, $service->getUserStatus(new User()));
    }

    public function testGetUserStatusReturnsStoredValueIncludingZero(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(OnlineStatusService::class);

        $user = new User();
        $user->setOnlineStatus(0);
        self::assertSame(0, $service->getUserStatus($user));

        $user->setOnlineStatus(4);
        self::assertSame(4, $service->getUserStatus($user));
    }
}
