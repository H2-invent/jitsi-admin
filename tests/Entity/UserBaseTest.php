<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UserBase;
use PHPUnit\Framework\TestCase;

class UserBaseTest extends TestCase
{
    private function user(): User
    {
        return new User();
    }

    public function testUserExtendsUserBase(): void
    {
        self::assertInstanceOf(UserBase::class, $this->user());
    }

    public function testIdIsNullBeforePersist(): void
    {
        self::assertNull($this->user()->getId());
    }

    public function testUuidRoundTrip(): void
    {
        $user = $this->user();

        self::assertSame($user, $user->setUuid('uuid-123'));
        self::assertSame('uuid-123', $user->getUuid());
    }

    public function testRolesAlwaysContainRoleUser(): void
    {
        $user = $this->user();

        self::assertSame($user, $user->setRoles(['ROLE_ADMIN']));
        $roles = $user->getRoles();
        self::assertContains('ROLE_ADMIN', $roles);
        self::assertContains('ROLE_USER', $roles);
    }

    public function testRolesWithNoRolesOnlyReturnRoleUser(): void
    {
        $user = $this->user()->setRoles([]);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testDuplicateRoleUserIsNotReturnedTwice(): void
    {
        $user = $this->user()->setRoles(['ROLE_USER', 'ROLE_USER']);

        self::assertCount(1, $user->getRoles());
        self::assertSame('ROLE_USER', $user->getRoles()[0]);
    }

    public function testPasswordRoundTripAndDefaultCast(): void
    {
        $user = $this->user();

        self::assertSame('', $user->getPassword());

        self::assertSame($user, $user->setPassword('hashed-secret'));
        self::assertSame('hashed-secret', $user->getPassword());
    }

    public function testSaltReturnsNull(): void
    {
        self::assertNull($this->user()->getSalt());
    }

    public function testEraseCredentialsReturnsNull(): void
    {
        self::assertNull($this->user()->eraseCredentials());
    }

    public function testGetUserIdentifierReturnsUsernameOnConcreteUser(): void
    {
        $user = $this->user()->setUsername('john.doe');

        self::assertSame('john.doe', $user->getUserIdentifier());
    }
}
