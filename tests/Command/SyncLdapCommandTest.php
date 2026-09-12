<?php

namespace App\Tests\Command;

use App\Command\SyncLdapCommand;
use App\dataType\LdapType;
use App\Entity\LdapUserProperties;
use App\Entity\User;
use App\Service\ldap\LdapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SyncLdapCommandTest extends KernelTestCase
{
    private function createLdapType(string $url, bool $healthy): LdapType
    {
        $ldapType = new LdapType();
        $ldapType->setUrl($url);
        $ldapType->setUserDn('o=testOrg,dc=example,dc=com');
        $ldapType->setSerVerId('ldap_1');
        $ldapType->setIsHealthy($healthy);

        return $ldapType;
    }

    private function createLdapUser(string $email): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setUsername($email)
            ->setFirstName('Sync')
            ->setLastName('User');
        $properties = (new LdapUserProperties())
            ->setUser($user)
            ->setLdapHost('ldap://healthy.local')
            ->setLdapDn('cn=' . $email . ',dc=example,dc=com')
            ->setRdn('uid=' . $email)
            ->setLdapNumber('ldap_1');
        $user->setLdapUserProperties($properties);

        return $user;
    }

    public function testSyncHealthyLdap(): void
    {
        self::bootKernel();
        $ldapType = $this->createLdapType('ldap://healthy.local', true);
        $users = [$this->createLdapUser('sync-one@local.de'), $this->createLdapUser('sync-two@local.de')];

        $ldapService = $this->createMock(LdapService::class);
        $ldapService->expects($this->once())->method('initLdap');
        $ldapService->expects($this->once())->method('testLdap');
        $ldapService->expects($this->once())->method('getLdaps')->willReturn([$ldapType]);
        $ldapService->expects($this->once())->method('fetchLdap')->willReturn(['ldap' => $ldapType, 'user' => $users]);
        $ldapService->expects($this->once())->method('cleanUpLdapUsers');
        self::getContainer()->set(LdapService::class, $ldapService);

        $command = self::getContainer()->get(SyncLdapCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('We found # users: 2', $display);
        $this->assertStringContainsString('All LDAPS could be synced correctly', $display);
        $this->assertStringContainsString('sync-one@local.de', $display);
        $this->assertStringContainsString('sync-two@local.de', $display);
    }

    public function testDryRunSkipsCleanup(): void
    {
        self::bootKernel();
        $ldapType = $this->createLdapType('ldap://healthy.local', true);
        $users = [$this->createLdapUser('sync-dry-run@local.de')];

        $ldapService = $this->createMock(LdapService::class);
        $ldapService->expects($this->once())->method('getLdaps')->willReturn([$ldapType]);
        $ldapService->expects($this->once())->method('fetchLdap')->with($ldapType, true)->willReturn(['ldap' => $ldapType, 'user' => $users]);
        $ldapService->expects($this->never())->method('cleanUpLdapUsers');
        self::getContainer()->set(LdapService::class, $ldapService);

        $command = self::getContainer()->get(SyncLdapCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Dryrun is activated. No databases changes are made', $display);
        $this->assertStringContainsString('We found # users: 1', $display);
        $this->assertStringNotContainsString('We cleanup Users which are not in the LDAP anymore', $display);
    }

    public function testUnhealthyLdapReportsErrorButSucceeds(): void
    {
        self::bootKernel();
        $ldapType = $this->createLdapType('ldap://unhealthy.local', false);

        $ldapService = $this->createMock(LdapService::class);
        $ldapService->expects($this->once())->method('getLdaps')->willReturn([$ldapType]);
        $ldapService->expects($this->never())->method('fetchLdap');
        $ldapService->expects($this->once())->method('cleanUpLdapUsers');
        self::getContainer()->set(LdapService::class, $ldapService);

        $command = self::getContainer()->get(SyncLdapCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('This LDAP is unhealty: ldap://unhealthy.local', $display);
        $this->assertStringContainsString('We found # users: 0', $display);
        $this->assertSame(0, $tester->getStatusCode());
    }
}
