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
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $ldapService->testLdap();
        $res = $ldapService->fetchLdap($ldapService->getLdaps()[2]);
        assertEquals(2, sizeof($res['user']));
    }



    public function testAddSIPVideoToUser(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $ldapService->testLdap();
        $res = $ldapService->fetchLdap($ldapService->getLdaps()[2]);
        assertEquals(2, sizeof($res['user']));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('testsipvideo');
        self::assertTrue($user->isIsSipVideoUser());
    }
    public function testnoSipVideoUser(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $ldapService->testLdap();
        $ldapType = $ldapService->getLdaps()[2];
        $ldapType->setISSIPVIDEO(false);
        $res = $ldapService->fetchLdap($ldapService->getLdaps()[2]);
        assertEquals(2, sizeof($res['user']));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('testsipvideo');
        self::assertFalse($user->isIsSipVideoUser());
        $ldapType->setISSIPVIDEO(true);
        $res = $ldapService->fetchLdap($ldapService->getLdaps()[2]);
        assertEquals(2, sizeof($res['user']));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('testsipvideo');
        self::assertTrue($user->isIsSipVideoUser());
    }


    public function testAddSIPVideoToUserDryrun(): void
    {
        $kernel = self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $ldapService->testLdap();
        $ldapType = $ldapService->getLdaps()[2];
        $ldapType->setISSIPVIDEO(false);
        $res = $ldapService->fetchLdap($ldapService->getLdaps()[2], true);
        assertEquals(2, sizeof($res['user']));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneByEmail('testsipvideo');
        self::assertNull($user);
    }





}
