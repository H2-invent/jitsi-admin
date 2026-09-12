<?php

namespace App\Tests\Twig;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Twig\CheckStartTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class CheckStartTimeTest extends KernelTestCase
{
    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(CheckStartTime::class);

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('isRoomOpen', $functions[0]->getName());
        $this->assertSame([$extension, 'isRoomOpen'], $functions[0]->getCallable());
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
        $this->assertNull(call_user_func($extension->getFunctions()[0]->getCallable(), $room, $user));
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
