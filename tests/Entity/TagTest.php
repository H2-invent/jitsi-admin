<?php

namespace App\Tests\Entity;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
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
