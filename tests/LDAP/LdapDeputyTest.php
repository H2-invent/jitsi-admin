<?php

namespace App\Tests\LDAP;

use App\Service\Deputy\DebutyLdapService;
use App\Service\ldap\LdapService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use function PHPUnit\Framework\assertEquals;

class LdapDeputyTest extends KernelTestCase
{
    public function testFetchDeputiesFromLDap(): void
    {
        $kernel = self::bootKernel();


        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $ldapService->testLdap();
        $res = $ldapService->fetchDeputies();
        assertEquals(1, sizeof($res));
    }

    public function testFetchDeputiesFromLDapWrongFilter(): void
    {
        $kernel = self::bootKernel();


        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $ldapService->testLdap();
        $connection1 = $ldapService->getLdaps()[0];
        self::assertNull($connection1->getLDAPDEPUTYGROUPFILTER());
        $connection2 = $ldapService->getLdaps()[1];
        $res = $connection2->retrieveDeputies();
        assertEquals(0, sizeof($res));
        assertEquals(1, sizeof($connection1->retrieveDeputies()));
    }


    public function testCleanDeputys(): void
    {
        $kernel = self::bootKernel();


        $ldapDeputy = self::getContainer()->get(DebutyLdapService::class);

        assertEquals(2, $ldapDeputy->cleanDeputies());
    }

    public function testaddNewDeputies(): void
    {
        $kernel = self::bootKernel();


        $ldapDeputy = self::getContainer()->get(DebutyLdapService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();
        $ldapService->testLdap();
        assertEquals(2, $ldapDeputy->cleanDeputies());
        foreach ($ldapService->getLdaps() as $data) {
            $ldapService->fetchLdap($data);
        }
        $ldapService->setDeputies($ldapService->fetchDeputies());
    }
    public function testhwithCommand(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:ldap:deputy:create');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(' [INFO] Try to connect to: ldap_1  ', $output);
        $this->assertStringContainsString(' [INFO] Try to connect to: ldap_2', $output);
        $this->assertStringContainsString(' [OK] Sucessfully connect to ldap://192.168.230.130:389 ', $output);
        $this->assertStringContainsString(' [OK] We connect all LDAP Deputies', $output);
    }
}
