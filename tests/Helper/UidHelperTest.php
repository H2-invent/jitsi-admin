<?php

namespace App\Tests\Helper;

use App\Entity\Rooms;
use App\Helper\UidHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UidHelperTest extends TestCase
{
    private function createHelper(): UidHelper
    {
        return new UidHelper($this->createMock(EntityManagerInterface::class));
    }

    public function testGetUidReturnsUidRealWhenSet(): void
    {
        $room = new Rooms();
        $room->setUid('room-uid');
        $room->setUidReal('real-uid');

        self::assertSame('real-uid', $this->createHelper()->getUid($room));
    }

    public function testGetUidFallsBackToUidWhenUidRealIsMissing(): void
    {
        $room = new Rooms();
        $room->setUid('room-uid');

        self::assertSame('room-uid', $this->createHelper()->getUid($room));
    }

    public function testGetUidUsesRepeaterUidWhenRoomBelongsToSeries(): void
    {
        $repeater = new \App\Entity\Repeat();
        $repeater->setUid('repeater-uid');
        $room = new Rooms();
        $room->setUidReal('real-uid');
        $room->setRepeater($repeater);

        self::assertSame('repeater-uid', $this->createHelper()->getUid($room));
    }
}
