<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\InMemoryUser;

#[CoversClass(UserProvider::class)]
class UserProviderTest extends KernelTestCase
{
    private UserProvider $provider;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->provider = new UserProvider(self::getContainer()->get(EntityManagerInterface::class));
    }

    public function testLoadUserByUsernameBuildsQueryWithoutFromClauseAndFails(): void
    {
        // Production issue: createQueryBuilder('u') ignores the alias and no from()/select() is added.
        $this->expectException(QueryException::class);
        $this->provider->loadUserByUsername('test@local.de');
    }

    public function testRefreshUserReturnsTheSameInstanceForSupportedUser(): void
    {
        $user = new User();

        self::assertSame($user, $this->provider->refreshUser($user));
    }

    public function testRefreshUserRejectsUserOfAnotherClass(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(InMemoryUser::class);

        $this->provider->refreshUser(new InMemoryUser('foreign-user', null));
    }

    public function testSupportsClassOnlyMatchesTheLegacySecurityUserString(): void
    {
        self::assertTrue($this->provider->supportsClass('App\Security\User'));
        self::assertFalse($this->provider->supportsClass(User::class));
        self::assertFalse($this->provider->supportsClass(\stdClass::class));
    }

    public function testLoadUserByIdentifierThrowsTypeErrorBecauseNothingIsReturned(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('none returned');

        $this->provider->loadUserByIdentifier('test@local.de');
    }
}
