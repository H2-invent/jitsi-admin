<?php

namespace App\Tests\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\Twig\Utils;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UtilsTest extends KernelTestCase
{
    private function extension(): Utils
    {
        return self::getContainer()->get(Utils::class);
    }

    private function filterByName(Utils $extension, string $name): TwigFilter
    {
        foreach ($extension->getFilters() as $filter) {
            if ($filter->getName() === $name) {
                return $filter;
            }
        }
        self::fail(sprintf('Twig filter "%s" not registered', $name));
    }

    private function functionByName(Utils $extension, string $name): TwigFunction
    {
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }
        self::fail(sprintf('Twig function "%s" not registered', $name));
    }

    public function testGetFiltersReturnsExpectedTwigFilters(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $filters = $extension->getFilters();
        $names = array_map(static fn(TwigFilter $filter) => $filter->getName(), $filters);

        $this->assertSame(['addRepetiveCharacters', 'json_decode', 'colorFromString'], $names);
        $this->assertSame([$extension, 'addRepetiveCharacters'], $filters[0]->getCallable());
        $this->assertSame([$extension, 'json_decode'], $filters[1]->getCallable());
        $this->assertSame([$extension, 'colorFromString'], $filters[2]->getCallable());
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('roomIsReadOnly', $functions[0]->getName());
        $this->assertSame([$extension, 'roomIsReadOnly'], $functions[0]->getCallable());
    }

    public function testAddRepetiveCharactersInsertsCharacterEverySequence(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame('123-456-789-', $extension->addRepetiveCharacters('123456789', '-', 3));
        $this->assertSame('ab#cd#ef#', $extension->addRepetiveCharacters('abcdef', '#', 2));
        $this->assertSame(
            '123-456-789-',
            call_user_func($this->filterByName($extension, 'addRepetiveCharacters')->getCallable(), '123456789', '-', 3)
        );
    }

    public function testJsonDecodeReturnsArrayForJsonObject(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame(['a' => 1, 'b' => [2, 3]], $extension->json_decode('{"a":1,"b":[2,3]}'));
        $this->assertSame(
            ['a' => 1],
            call_user_func($this->filterByName($extension, 'json_decode')->getCallable(), '{"a":1}')
        );
    }

    public function testJsonDecodeReturnsNullForNullAndInvalidJson(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertNull($extension->json_decode(null));
        $this->assertNull($extension->json_decode('not-json'));
    }

    public function testColorFromStringReturnsSixHexCharactersFromCrc32(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame('d87f7e', $extension->colorFromString('test'));
        $this->assertSame($extension->colorFromString('test'), $extension->colorFromString('test'));
        $this->assertNotSame($extension->colorFromString('test'), $extension->colorFromString('other'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{1,6}$/', $extension->colorFromString('some user name'));
    }

    public function testRoomIsReadOnlyReflectsModeratorAndMemberMembership(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $moderator = (new User())->setUsername('moderator');
        $moderatedRoom = (new Rooms())->setModerator($moderator)->setCreator($moderator);
        $this->assertFalse($extension->roomIsReadOnly($moderatedRoom, $moderator));

        $member = (new User())->setUsername('member');
        $room = new Rooms();
        $room->addUser($member);
        $this->assertFalse($extension->roomIsReadOnly($room, $member));

        $outsider = (new User())->setUsername('outsider');
        $this->assertTrue($extension->roomIsReadOnly($room, $outsider));
    }

    public function testRoomIsReadOnlyViaRegisteredCallable(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $moderator = (new User())->setUsername('moderator');
        $room = (new Rooms())->setModerator($moderator)->setCreator($moderator);

        $this->assertFalse(
            call_user_func($this->functionByName($extension, 'roomIsReadOnly')->getCallable(), $room, $moderator)
        );
    }
}
