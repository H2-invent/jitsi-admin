<?php

namespace App\Tests\Twig;

use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Twig\Reporting;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class ReportingTest extends TestCase
{

    private function functionNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function filterNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFilter::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function extension(): Reporting
    {
        return new Reporting();
    }

    private function functionByName(Reporting $extension, string $name): string
    {
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                if ($attribute->newInstance()->name === $name) {
                    return $method->getName();
                }
            }
        }
        self::fail(sprintf('Twig Function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = $this->extension();
        $this->assertSame(["getTotalSpeakingTime"], $this->functionNames($extension));
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
            call_user_func([$extension, $this->functionByName($extension, 'getTotalSpeakingTime')], $roomStatus)
        );
    }
}
