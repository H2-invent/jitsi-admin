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

    private function extension(): Utils
    {
        return self::getContainer()->get(Utils::class);
    }

    public function testGetFiltersReturnsExpectedTwigFilters(): void
    {
        $extension = $this->extension();
        $this->assertSame(["addRepetiveCharacters", "json_decode", "colorFromString"], $this->filterNames($extension));
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = $this->extension();
        $this->assertSame(["roomIsReadOnly"], $this->functionNames($extension));
    }

    public function testAddRepetiveCharactersInsertsCharacterEverySequence(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame('123-456-789-', $extension->addRepetiveCharacters('123456789', '-', 3));
        $this->assertSame('ab#cd#ef#', $extension->addRepetiveCharacters('abcdef', '#', 2));
    }

    public function testJsonDecodeReturnsArrayForJsonObject(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame(['a' => 1, 'b' => [2, 3]], $extension->json_decode('{"a":1,"b":[2,3]}'));
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
}
