<?php

namespace App\Tests\Rooms;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class RoomsCloneTest extends TestCase
{
    public function testCloneResetsIdentityAndCollections(): void
    {
        $room = new Rooms();
        $room->setName('Prototype');
        $room->setDuration(60);

        $user = new User();
        $room->addUser($user);

        $attribute = new RoomsUser();
        $attribute->setUser($user);
        $room->addUserAttribute($attribute);

        $callerRoom = new CallerRoom();
        $room->setCallerRoom($callerRoom);

        $clone = clone $room;

        self::assertNull($clone->getId());
        self::assertSame('Prototype', $clone->getName());
        self::assertSame(60.0, $clone->getDuration());
        self::assertNotSame($room->getUser(), $clone->getUser());
        self::assertCount(0, $clone->getUser());
        self::assertNotSame($room->getUserAttributes(), $clone->getUserAttributes());
        self::assertCount(0, $clone->getUserAttributes());
        self::assertNull($clone->getCallerRoom());

        // the source room keeps its own collections untouched
        self::assertCount(1, $room->getUser());
        self::assertCount(1, $room->getUserAttributes());
        self::assertSame($callerRoom, $room->getCallerRoom());
    }
}
