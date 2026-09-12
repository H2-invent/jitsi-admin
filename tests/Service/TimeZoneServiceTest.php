<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\TimeZoneService;
use PHPUnit\Framework\TestCase;

class TimeZoneServiceTest extends TestCase
{
    public function testGetTimeZoneReturnsNullForNullUser(): void
    {
        self::assertNull(TimeZoneService::getTimeZone(null));
    }

    public function testGetTimeZoneReturnsNullWhenUserHasNoTimeZone(): void
    {
        self::assertNull(TimeZoneService::getTimeZone(new User()));
    }

    public function testGetTimeZoneReturnsConfiguredZone(): void
    {
        $user = new User();
        $user->setTimeZone('Europe/Berlin');

        $timeZone = TimeZoneService::getTimeZone($user);

        self::assertInstanceOf(\DateTimeZone::class, $timeZone);
        self::assertSame('Europe/Berlin', $timeZone->getName());
    }

    public function testGetTimeZoneThrowsForInvalidZone(): void
    {
        $user = new User();
        $user->setTimeZone('Not/AZone');

        $this->expectException(\Exception::class);

        TimeZoneService::getTimeZone($user);
    }
}
