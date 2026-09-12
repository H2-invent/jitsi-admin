<?php

namespace App\Tests\Entity;

use App\Entity\Log;
use App\Entity\Rooms;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $log = new Log();

        self::assertNull($log->getId());
        self::assertNull($log->getCreatedAt());
        self::assertNull($log->getUserName());
        self::assertNull($log->getMessage());
        self::assertNull($log->getRoom());
        self::assertNull($log->getUser());
    }

    public function testSettersRoundTripAndReturnSelf(): void
    {
        $log = new Log();
        $createdAt = new \DateTimeImmutable('2024-01-02 03:04:05');
        $room = new Rooms();
        $user = new User();

        self::assertSame($log, $log->setCreatedAt($createdAt));
        self::assertSame($log, $log->setUserName('alice'));
        self::assertSame($log, $log->setMessage('hello world'));
        self::assertSame($log, $log->setRoom($room));
        self::assertSame($log, $log->setUser($user));

        self::assertSame($createdAt, $log->getCreatedAt());
        self::assertSame('alice', $log->getUserName());
        self::assertSame('hello world', $log->getMessage());
        self::assertSame($room, $log->getRoom());
        self::assertSame($user, $log->getUser());
    }

    public function testRelationsCanBeResetToNull(): void
    {
        $log = (new Log())
            ->setRoom(new Rooms())
            ->setUser(new User());

        self::assertNull($log->setRoom(null)->getRoom());
        self::assertNull($log->setUser(null)->getUser());
    }
}
