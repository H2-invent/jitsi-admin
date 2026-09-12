<?php

namespace App\Tests\Twig;

use App\Entity\CallerId;
use App\Entity\Rooms;
use App\Entity\User;
use App\Twig\SipCallIn;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class SipCallInTest extends TestCase
{
    private function createCallerId(User $user, Rooms $room, string $pin): CallerId
    {
        $callerId = new CallerId();
        $callerId->setRoom($room)->setCallerId($pin);
        $user->addCallerId($callerId);

        return $callerId;
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new SipCallIn();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('sipPinFromRoomAndUser', $functions[0]->getName());
        $this->assertSame([$extension, 'sipPinFromRoomAndUser'], $functions[0]->getCallable());
    }

    public function testSipPinFromRoomAndUserReturnsMatchingCallerId(): void
    {
        $extension = new SipCallIn();
        $user = new User();
        $room = new Rooms();
        $callerId = $this->createCallerId($user, $room, '4242');

        $this->assertSame($callerId, $extension->sipPinFromRoomAndUser($room, $user));
    }

    public function testSipPinFromRoomAndUserReturnsNullForOtherRoom(): void
    {
        $extension = new SipCallIn();
        $user = new User();
        $room = new Rooms();
        $otherRoom = new Rooms();
        $this->createCallerId($user, $room, '4242');

        $this->assertNull($extension->sipPinFromRoomAndUser($otherRoom, $user));
    }

    public function testSipPinFromRoomAndUserReturnsNullWithoutCallerIds(): void
    {
        $extension = new SipCallIn();

        $this->assertNull($extension->sipPinFromRoomAndUser(new Rooms(), new User()));
    }

    public function testSipPinFromRoomAndUserPicksCorrectEntryAmongMany(): void
    {
        $extension = new SipCallIn();
        $user = new User();
        $roomOne = new Rooms();
        $roomTwo = new Rooms();
        $this->createCallerId($user, $roomOne, '1111');
        $expected = $this->createCallerId($user, $roomTwo, '2222');

        $this->assertSame($expected, call_user_func($extension->getFunctions()[0]->getCallable(), $roomTwo, $user));
        $this->assertSame('2222', $extension->sipPinFromRoomAndUser($roomTwo, $user)->getCallerId());
    }
}
