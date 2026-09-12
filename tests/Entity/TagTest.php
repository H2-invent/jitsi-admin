<?php

namespace App\Tests\Entity;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testDefaults(): void
    {
        $tag = new Tag();

        self::assertNull($tag->getId());
        self::assertNull($tag->getTitle());
        self::assertFalse($tag->getDisabled());
        self::assertNull($tag->getPriority());
        self::assertNull($tag->getColor());
        self::assertNull($tag->getBackgroundColor());
        self::assertCount(0, $tag->getRooms());
        self::assertCount(0, $tag->getServers());
    }

    public function testSettersRoundTripAndReturnSelf(): void
    {
        $tag = new Tag();

        self::assertSame($tag, $tag->setTitle('Room tag'));
        self::assertSame($tag, $tag->setDisabled(true));
        self::assertSame($tag, $tag->setPriority(7));
        self::assertSame($tag, $tag->setColor('#fff'));
        self::assertSame($tag, $tag->setBackgroundColor('#000'));

        self::assertSame('Room tag', $tag->getTitle());
        self::assertTrue($tag->getDisabled());
        self::assertSame(7, $tag->getPriority());
        self::assertSame('#fff', $tag->getColor());
        self::assertSame('#000', $tag->getBackgroundColor());
    }

    public function testNullablePriorityColorAndBackgroundColorCanBeReset(): void
    {
        $tag = (new Tag())
            ->setPriority(1)
            ->setColor('#fff')
            ->setBackgroundColor('#000');

        self::assertNull($tag->setPriority(null)->getPriority());
        self::assertNull($tag->setColor(null)->getColor());
        self::assertNull($tag->setBackgroundColor(null)->getBackgroundColor());
    }

    public function testAddAndRemoveRoomKeepsBothSidesInSync(): void
    {
        $tag = new Tag();
        $room = new Rooms();

        self::assertSame($tag, $tag->addRoom($room));
        self::assertCount(1, $tag->getRooms());
        self::assertTrue($tag->getRooms()->contains($room));
        self::assertSame($tag, $room->getTag());

        $tag->addRoom($room);
        self::assertCount(1, $tag->getRooms());

        self::assertSame($tag, $tag->removeRoom($room));
        self::assertCount(0, $tag->getRooms());
        self::assertNull($room->getTag());
    }

    public function testAddServerKeepsBothSidesInSync(): void
    {
        $tag = new Tag();
        $server = new Server();

        self::assertSame($tag, $tag->addServer($server));
        self::assertCount(1, $tag->getServers());
        self::assertTrue($tag->getServers()->contains($server));
        self::assertTrue($server->getTag()->contains($tag));

        $tag->addServer($server);
        self::assertCount(1, $tag->getServers());
        self::assertCount(1, $server->getTag());
    }

    public function testRemoveServerDetachesBothSides(): void
    {
        $tag = new Tag();
        $server = new Server();
        $tag->addServer($server);

        self::assertSame($tag, $tag->removeServer($server));
        self::assertCount(0, $tag->getServers());
        self::assertFalse($server->getTag()->contains($tag));
    }
}
