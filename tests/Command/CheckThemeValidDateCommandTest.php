<?php

namespace App\Tests\Command;

use App\Command\CheckThemeValidDateCommand;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;

class CheckThemeValidDateCommandTest extends KernelTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'theme-valid-date-' . uniqid();
        (new Filesystem())->mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'theme');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
        parent::tearDown();
    }

    private function createCommand(): CheckThemeValidDateCommand
    {
        return new CheckThemeValidDateCommand(
            new ParameterBag(['kernel.project_dir' => $this->tempDir]),
            self::getContainer()->get(MailerService::class)
        );
    }

    private function writeThemeFile(string $validUntil, ?string $contactEmail = null): string
    {
        $file = $this->tempDir . '/theme/' . uniqid('phpunit_', true) . 'theme.json.signed';
        $entry = ['validUntil' => $validUntil];
        if ($contactEmail !== null) {
            $entry['contactEmail'] = $contactEmail;
        }
        file_put_contents($file, json_encode(['entry' => $entry]));

        return $file;
    }

    public function testSendsMailForThemeExpiringSoon(): void
    {
        self::bootKernel();
        $this->writeThemeFile((new \DateTime('+5 days'))->format('Y-m-d'), 'theme-contact@local.de');

        $tester = new CommandTester($this->createCommand());
        $tester->execute(['maxTime' => 10]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('[OK] We send 1 emails with expiring Themes', $tester->getDisplay());
    }

    public function testDoesNotSendMailForThemeExpiringLate(): void
    {
        self::bootKernel();
        $this->writeThemeFile((new \DateTime('+100 days'))->format('Y-m-d'));

        $tester = new CommandTester($this->createCommand());
        $tester->execute(['maxTime' => 10]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('[OK] We send 0 emails with expiring Themes', $tester->getDisplay());
    }

    public function testDoesNotSendMailForAlreadyExpiredTheme(): void
    {
        self::bootKernel();
        $this->writeThemeFile((new \DateTime('-5 days'))->format('Y-m-d'));

        $tester = new CommandTester($this->createCommand());
        $tester->execute(['maxTime' => 10]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('[OK] We send 0 emails with expiring Themes', $tester->getDisplay());
    }
}
