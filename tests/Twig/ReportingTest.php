<?php

namespace App\Tests\Twig;

use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Twig\Reporting;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class ReportingTest extends TestCase
{
    private function extension(): Reporting
    {
        return new Reporting();
    }

    private function functionByName(Reporting $extension, string $name): TwigFunction
    {
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }
        self::fail(sprintf('Twig function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = $this->extension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('getTotalSpeakingTime', $functions[0]->getName());
        $this->assertSame([$extension, 'getTotalSpeakingTime'], $functions[0]->getCallable());
    }

    public function testGetTotalSpeakingTimeSumsDominantSpeakerTimes(): void
    {
        $extension = $this->extension();
        $roomStatus = new RoomStatus();
        $roomStatus->addRoomStatusParticipant((new RoomStatusParticipant())->setDominantSpeakerTime(100));
        $roomStatus->addRoomStatusParticipant((new RoomStatusParticipant())->setDominantSpeakerTime(250));

        $this->assertSame(350, $extension->getTotalSpeakingTime($roomStatus));
    }

    public function testGetTotalSpeakingTimeIgnoresParticipantsWithoutDominantSpeakerTime(): void
    {
        $extension = $this->extension();
        $roomStatus = new RoomStatus();
        $roomStatus->addRoomStatusParticipant((new RoomStatusParticipant())->setDominantSpeakerTime(null));
        $roomStatus->addRoomStatusParticipant((new RoomStatusParticipant())->setDominantSpeakerTime(42));

        $this->assertSame(42, $extension->getTotalSpeakingTime($roomStatus));
    }

    public function testGetTotalSpeakingTimeReturnsZeroForRoomStatusWithoutParticipants(): void
    {
        $extension = $this->extension();

        $this->assertSame(0, $extension->getTotalSpeakingTime(new RoomStatus()));
    }

    public function testGetTotalSpeakingTimeViaRegisteredCallable(): void
    {
        $extension = $this->extension();
        $roomStatus = new RoomStatus();
        $roomStatus->addRoomStatusParticipant((new RoomStatusParticipant())->setDominantSpeakerTime(500));

        $this->assertSame(
            500,
            call_user_func($this->functionByName($extension, 'getTotalSpeakingTime')->getCallable(), $roomStatus)
        );
    }
}
