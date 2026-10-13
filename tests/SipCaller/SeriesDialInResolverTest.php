<?php

namespace App\Tests\SipCaller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Service\caller\SeriesDialInResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SeriesDialInResolverTest extends TestCase
{
    private function resolver(?Rooms $runningAppointment): SeriesDialInResolver
    {
        $roomsRepository = $this->createMock(RoomsRepository::class);
        $roomsRepository->method('findRunningRoomOfRepeater')->willReturn($runningAppointment);

        return new SeriesDialInResolver($roomsRepository, $this->createMock(LoggerInterface::class));
    }

    public function testNormalRoomStaysUntouched(): void
    {
        $room = new Rooms();

        self::assertSame($room, $this->resolver(new Rooms())->resolveRoom($room));
    }

    public function testAppointmentOfASeriesStaysUntouched(): void
    {
        $room = new Rooms();
        $room->setRepeater(new Repeat());

        self::assertSame($room, $this->resolver(new Rooms())->resolveRoom($room));
    }

    public function testPrototypeIsReplacedByTheRunningAppointment(): void
    {
        $repeater = new Repeat();
        $prototype = new Rooms();
        $prototype->setRepeaterProtoype($repeater);
        $appointment = new Rooms();
        $appointment->setRepeater($repeater);

        self::assertSame($appointment, $this->resolver($appointment)->resolveRoom($prototype));
    }

    public function testPrototypeStaysWhenNoAppointmentIsRunning(): void
    {
        $prototype = new Rooms();
        $prototype->setRepeaterProtoype(new Repeat());

        self::assertSame($prototype, $this->resolver(null)->resolveRoom($prototype));
    }

    public function testNullStaysNull(): void
    {
        self::assertNull($this->resolver(new Rooms())->resolveRoom(null));
    }
}
