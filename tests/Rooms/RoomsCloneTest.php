<?php

namespace App\Tests\Rooms;

use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Scheduling;
use App\Entity\Subscriber;
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

    public function testDuplicateCopiesParticipantsAndTheirAttributes(): void
    {
        $room = new Rooms();
        $room->setName('Prototype');

        $user = new User();
        $room->addUser($user);

        $attribute = new RoomsUser();
        $attribute->setUser($user);
        $attribute->setModerator(true);
        $attribute->setShareDisplay(true);
        $attribute->setPrivateMessage(false);
        $attribute->setLobbyModerator(true);
        $room->addUserAttribute($attribute);

        $copy = $room->duplicate();

        self::assertNotSame($room, $copy);
        self::assertNull($copy->getId());
        self::assertSame('Prototype', $copy->getName());
        self::assertCount(1, $copy->getUser());
        self::assertSame($user, $copy->getUser()->first());
        self::assertCount(1, $copy->getUserAttributes());

        $copiedAttribute = $copy->getUserAttributes()->first();
        self::assertNotSame($attribute, $copiedAttribute);
        self::assertSame($copy, $copiedAttribute->getRoom());
        self::assertSame($user, $copiedAttribute->getUser());
        self::assertTrue($copiedAttribute->getModerator());
        self::assertTrue($copiedAttribute->getShareDisplay());
        self::assertFalse($copiedAttribute->getPrivateMessage());
        self::assertTrue($copiedAttribute->getLobbyModerator());

        // the source keeps its own data
        self::assertCount(1, $room->getUser());
        self::assertCount(1, $room->getUserAttributes());
        self::assertSame($room, $attribute->getRoom());
    }

    public function testDuplicateDoesNotCopyDialInSchedulesOrSubscribers(): void
    {
        $room = new Rooms();
        $room->setCallerRoom(new CallerRoom());
        $room->addScheduling(new Scheduling());
        $room->addSubscriber(new Subscriber());

        $copy = $room->duplicate();

        self::assertNull($copy->getCallerRoom());
        self::assertCount(0, $copy->getSchedulings());
        self::assertCount(0, $copy->getSubscribers());

        self::assertNotNull($room->getCallerRoom());
        self::assertCount(1, $room->getSchedulings());
        self::assertCount(1, $room->getSubscribers());
    }
}
