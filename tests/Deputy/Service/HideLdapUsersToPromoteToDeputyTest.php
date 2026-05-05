<?php

namespace App\Tests\Deputy\Service;

use App\Entity\LdapUserProperties;
use App\Entity\User;
use App\Twig\DeputyTwig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HideLdapUsersToPromoteToDeputyTest extends KernelTestCase
{
    public function testUserHasNoLdap(): void
    {
        $kernel = self::bootKernel();
        $deputyTwigExtension = self::getContainer()->get(DeputyTwig::class);
        $user = new User();
        self::assertFalse($deputyTwigExtension->userIsDisallowedToMakeDeputy($user));


    }
    public function testUserHasLdapProperty(): void
    {
        $kernel = self::bootKernel();
        $deputyTwigExtension = self::getContainer()->get(DeputyTwig::class);
        $user = new User();
        $ldapProperty = new LdapUserProperties();
        $user->setLdapUserProperties($ldapProperty);
        $ldapProperty->setLdapNumber('test123');
        self::assertFalse($deputyTwigExtension->userIsDisallowedToMakeDeputy($user));
    }
    public function testUserHasLdapPropertyAndIsBlocked(): void
    {
        $kernel = self::bootKernel();
        $deputyTwigExtension = self::getContainer()->get(DeputyTwig::class);
        $user = new User();
        $ldapProperty = new LdapUserProperties();
        $user->setLdapUserProperties($ldapProperty);
        $ldapProperty->setLdapNumber('ldap_3');
        self::assertTrue($deputyTwigExtension->userIsDisallowedToMakeDeputy($user));
        $ldapProperty->setLdapNumber('test_ldap');
        self::assertTrue($deputyTwigExtension->userIsDisallowedToMakeDeputy($user));
    }
}
