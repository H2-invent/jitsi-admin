<?php

namespace App\Tests\LDAP;

use App\Service\Deputy\DebutyLdapService;
use App\Service\ldap\LdapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function PHPUnit\Framework\assertEquals;

class LdapDeputyTest extends KernelTestCase
{
    public function testFetchDeputiesFromLDap(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $res = $ldapService->fetchDeputies();
        assertEquals(1, sizeof($res));


    }

    public function testCleanDeputys(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $ldapDeputy = self::getContainer()->get(DebutyLdapService::class);

        assertEquals(2, $ldapDeputy->cleanDeputies());

    }

    public function testaddNewDeputies(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $ldapDeputy = self::getContainer()->get(DebutyLdapService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        assertEquals(2, $ldapDeputy->cleanDeputies());
        foreach ($ldapService->getLdaps() as $data){
            $ldapService->fetchLdap($data);
        }
        $ldapService->setDeputies($ldapService->fetchDeputies());

    }
}
