<?php

namespace App\Tests\Twig;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Twig\CheckStartTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class CheckStartTimeTest extends KernelTestCase
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

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = self::getContainer()->get(CheckStartTime::class);
        $this->assertSame(["isRoomOpen"], $this->functionNames($extension));
    }

    public function testPersistentRoomIsAlwaysOpen(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(CheckStartTime::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'This is a fixed room']);

        $this->assertNotNull($room);
        $this->assertNull($extension->isRoomOpen($room, $user));
    }

    public function testCurrentlyRunningRoomIsOpen(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(CheckStartTime::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Running Room']);

        $this->assertNotNull($room);
        $this->assertNotSame($user, $room->getModerator());
    }

    public function testFutureRoomIsClosedAndReturnsMessage(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(CheckStartTime::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Room Tomorrow']);

        $this->assertNotNull($room);
        $this->assertNotSame($user, $room->getModerator());
        $result = $extension->isRoomOpen($room, $user);
        $this->assertIsString($result);
        $this->assertNotSame('', $result);
    }
}
