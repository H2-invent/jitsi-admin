<?php

namespace App\Tests\Command;

use App\Command\LdapDeputyCreateCommand;
use App\Repository\DeputyRepository;
use App\Service\ldap\LdapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LdapDeputyCreateCommandTest extends KernelTestCase
{
    private function mockLdapService(): void
    {
        $ldapService = $this->createMock(LdapService::class);
        $ldapService->expects($this->once())->method('initLdap');
        $ldapService->expects($this->once())->method('testLdap');
        $ldapService->expects($this->once())->method('fetchDeputies')->willReturn([]);
        $ldapService->expects($this->once())->method('setDeputies')->with([]);

        self::getContainer()->set(LdapService::class, $ldapService);
    }

    public function testDryRunKeepsDeputies(): void
    {
        self::bootKernel();
        $this->mockLdapService();

        $repository = self::getContainer()->get(DeputyRepository::class);
        $this->assertCount(2, $repository->findBy(['isFromLdap' => true]));

        $command = self::getContainer()->get(LdapDeputyCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Dryrun is activated. No databases changes are made', $display);
        $this->assertStringContainsString('We connect all LDAP Deputies', $display);
        $this->assertCount(2, $repository->findBy(['isFromLdap' => true]));
    }

    public function testRunRemovesLdapDeputies(): void
    {
        self::bootKernel();
        $this->mockLdapService();

        $repository = self::getContainer()->get(DeputyRepository::class);
        $this->assertCount(2, $repository->findBy(['isFromLdap' => true]));

        $command = self::getContainer()->get(LdapDeputyCreateCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringNotContainsString('Dryrun is activated', $display);
        $this->assertStringContainsString('We connect all LDAP Deputies', $display);
        $this->assertCount(0, $repository->findBy(['isFromLdap' => true]));
    }
}
