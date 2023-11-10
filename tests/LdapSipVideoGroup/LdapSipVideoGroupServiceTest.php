<?php

namespace App\Tests\LdapSipVideoGroup;

use App\Service\ldap\LdapService;
use App\Service\ldap\LdapSipVideoGroupService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function PHPUnit\Framework\assertEquals;

class LdapSipVideoGroupServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $ldapVideoGroup = self::getContainer()->get(LdapSipVideoGroupService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapService->initLdap();

        $res = $ldapVideoGroup->DetectUserIsSipVideoUser($ldapService->getLdaps()[1],false);
    dump($res);
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
}
