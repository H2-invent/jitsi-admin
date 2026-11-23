<?php

namespace App\Tests\Twig;

use App\Entity\LobbyWaitungUser;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Twig\Name;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Markup;
use Twig\TwigFilter;

class NameTest extends KernelTestCase
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

    private function extension(): Name
    {
        return self::getContainer()->get(Name::class);
    }

    public function testGetFiltersReturnsExpectedTwigFilters(): void
    {
        $extension = $this->extension();
        $this->assertSame(["nameOfUser", "nameOfUserNoSymbol"], $this->filterNames($extension));
    }

    public function testNameOfUserReturnsMarkupWithSearchIconAndFormattedName(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');

        $result = $extension->nameOfUser($user);

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertSame(
            '<i class="fa fa-phone" title="0123456789" data-toggle="tooltip"></i> Test1, 1234, User, Test',
            (string) $result
        );
    }

    public function testNameOfUserNoSymbolReturnsFormattedNameWithoutIcon(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');

        $this->assertSame('Test1, 1234, User, Test', $extension->nameOfUserNoSymbol($user));
    }

    public function testNameOfUserEscapesScriptTagsInUserControlledFields(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = new User();
        $user->setUsername('scripter')
            ->setSpezialProperties(['ou' => 'X', 'departmentNumber' => 'Y'])
            ->setLastName('Z')
            ->setFirstName('<script>alert(1)</script>');

        $this->assertSame(
            'X, Y, Z, <&lt;script&gt;alert(1)&lt;/script&gt;',
            (string) $extension->nameOfUser($user)
        );
    }

    public function testNameOfUserResolvesUserFromLobbyWaitingUser(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $lobbyUser = (new LobbyWaitungUser())->setUser($user)->setShowName('Lobby Name');

        $this->assertSame((string) $extension->nameOfUser($user), (string) $extension->nameOfUser($lobbyUser));
    }

    public function testNameOfUserNoSymbolReturnsLobbyShowName(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $lobbyUser = (new LobbyWaitungUser())->setUser($user)->setShowName('Lobby Name');

        $this->assertSame('Lobby Name', $extension->nameOfUserNoSymbol($lobbyUser));
    }
}
