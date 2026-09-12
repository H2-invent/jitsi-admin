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
    private function extension(): Name
    {
        return self::getContainer()->get(Name::class);
    }

    private function filterByName(Name $extension, string $name): TwigFilter
    {
        foreach ($extension->getFilters() as $filter) {
            if ($filter->getName() === $name) {
                return $filter;
            }
        }
        self::fail(sprintf('Twig filter "%s" not registered', $name));
    }

    public function testGetFiltersReturnsExpectedTwigFilters(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $filters = $extension->getFilters();
        $names = array_map(static fn(TwigFilter $filter) => $filter->getName(), $filters);

        $this->assertSame(['nameOfUser', 'nameOfUserNoSymbol'], $names);
        $this->assertSame([$extension, 'nameOfUser'], $filters[0]->getCallable());
        $this->assertSame([$extension, 'nameOfUserNoSymbol'], $filters[1]->getCallable());
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
        $this->assertSame(
            'Test1, 1234, User, Test',
            call_user_func($this->filterByName($extension, 'nameOfUserNoSymbol')->getCallable(), $user)
        );
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
        $this->assertSame(
            'Lobby Name',
            call_user_func($this->filterByName($extension, 'nameOfUserNoSymbol')->getCallable(), $lobbyUser)
        );
    }

    public function testNameOfUserViaRegisteredCallable(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');

        $result = call_user_func($this->filterByName($extension, 'nameOfUser')->getCallable(), $user);

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertStringContainsString('Test1, 1234, User, Test', (string) $result);
    }
}
