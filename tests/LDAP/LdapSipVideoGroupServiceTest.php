<?php

namespace App\Tests\LDAP;

use App\Repository\UserRepository;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapSipVideoGroupService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function PHPUnit\Framework\assertEquals;

class LdapSipVideoGroupServiceTest extends KernelTestCase
{
    public function testFetch(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();

        $res = $ldapVideoGroup->fetchUserIsSipVideoUser($ldapService->getLdaps()[1]);
        assertEquals(1, $this->count($res));
    }
    public function testgetMembers(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();

        $res = $ldapVideoGroup->getMembersFromSip($ldapService->getLdaps()[1]);
        self::assertCount(2,$res);
    }
    public function testAddSIPVideoToUser(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
        self::assertNull($user->isIsSipVideoUser());
        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapVideoGroup->addSipAttributeToUser(['cn=ldapUser@local.de,dc=example,dc=com']);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());
    }

    public function testAddSIPVideoToUserDryrun(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
        self::assertNull($user->isIsSipVideoUser());
        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapVideoGroup->addSipAttributeToUser(['cn=ldapUser@local.de,dc=example,dc=com'],true);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
    }

    public function testAddSIPVideoToUserNotFound(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
        self::assertNull($user->isIsSipVideoUser());
        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapVideoGroup->addSipAttributeToUser(['cn=ldapUser@local,dc=example,dc=com']);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
    }

    public function testRemoveSIPVideoToUserisInList(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $user->setIsSipVideoUser(true);
        $manager->persist($user);
        $manager->flush();
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());


        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapVideoGroup->removeVideoSipFromUsersDnArray(['cn=ldapUser@local.de,dc=example,dc=com']);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());
    }
    public function testRemoveSIPVideoToUserNotisInList(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $user->setIsSipVideoUser(true);
        $manager->persist($user);
        $manager->flush();
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());


        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapVideoGroup->removeVideoSipFromUsersDnArray(['cn=ldapUser@local,dc=example,dc=com']);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
    }
    public function testRemoveSIPVideoToUserNotisInListDryrun(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $user->setIsSipVideoUser(true);
        $manager->persist($user);
        $manager->flush();
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());


        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapVideoGroup->removeVideoSipFromUsersDnArray(['cn=ldapUser@local,dc=example,dc=com'],true);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());
    }

    public function testgetMembersFromLdapTyps(): void
    {
        $kernel = self::bootKernel();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
        $ldapVideoGroup->connectSipVideoMembersFromLdapTypes($ldapService->getLdaps());
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());
    }

    public function testRemoveSIPVideoFromLdapTyps(): void
    {
        $kernel = self::bootKernel();
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        $manager = self::getContainer()->get(EntityManagerInterface::class);


        $user->setIsSipVideoUser(true);
        $user->getLdapUserProperties()->setLdapDn('cn=ldapUser@local,dc=example,dc=com');
        $manager->persist($user);
        $manager->flush();

        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertTrue($user->isIsSipVideoUser());


        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapVideoGroup->removeVideoSipFromUsers($ldapService->getLdaps());
        $user = $userRepo->findOneByEmail('ldapUser@local.de');
        self::assertNotTrue($user->isIsSipVideoUser());
    }


}
