<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserBaseTest extends TestCase
{
    private function user(): User
    {
        return new User();
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
}
