<?php

namespace App\Tests\Command;

use App\Command\InstallerCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;

class InstallerCommandTest extends KernelTestCase
{
    private string $tempDir;
    private array $envBackups = [];

    private const INSTALLER_CONF = 'installer' . DIRECTORY_SEPARATOR . 'jitsi-admin.conf';
    private const ENV_FILE = '.env.prod.local';

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'installer-' . uniqid();
        (new Filesystem())->mkdir($this->tempDir . DIRECTORY_SEPARATOR . 'installer');
        $this->prepareEnvironment();
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tempDir);
        foreach ($this->envBackups as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
        parent::tearDown();
    }

    public function testWritesConfigurationFilesUsingInteractiveAnswers(): void
    {
        $commandTester = new CommandTester($this->createCommand());
        $commandTester->setInputs([
            'https://installer-test.local',
            'no',
            'no',
            'http://keycloak.local',
            '',
            '',
            '',
            '',
        ]);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Configuration done!', $commandTester->getDisplay());

        $confPath = $this->tempDir . DIRECTORY_SEPARATOR . self::INSTALLER_CONF;
        $envPath = $this->tempDir . DIRECTORY_SEPARATOR . self::ENV_FILE;
        self::assertFileExists($confPath);
        self::assertFileExists($envPath);

        $conf = file_get_contents($confPath);
        self::assertStringContainsString('PORT=3000', $conf);
        self::assertStringContainsString('AWAY_TIME=5', $conf);
        self::assertMatchesRegularExpression('/WEBSOCKET_SECRET=.+/', $conf);

        $env = file_get_contents($envPath);
        self::assertStringContainsString('laF_baseUrl="https://installer-test.local"', $env);
        self::assertStringContainsString('DATABASE_URL="mysql://', $env);
        self::assertStringContainsString('MAILER_DSN="smtp://', $env);
        self::assertStringContainsString('OAUTH_KEYCLOAK_REALM="jitsi-admin"', $env);
    }

    public function testInvalidBaseUrlEventuallyFails(): void
    {
        $commandTester = new CommandTester($this->createCommand());
        $commandTester->setInputs(['not-a-valid-url', 'still-not-a-url', 'nope']);
        $commandTester->execute([]);

        self::assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Invalid URL', $commandTester->getDisplay());
        self::assertFileDoesNotExist($this->tempDir . DIRECTORY_SEPARATOR . self::ENV_FILE);
    }

    private function createCommand(): Command
    {
        $command = new InstallerCommand(new ParameterBag(['kernel.project_dir' => $this->tempDir]));
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));

        return $command;
    }

    private function prepareEnvironment(): void
    {
        $values = [
            'WEBSOCKET_SECRET' => 'DUMMY',
            'laF_baseUrl' => 'http://localhost:8000',
            'DATABASE_URL' => 'mysql://installer_user:installer_password@localhost:3306/installer_db?serverVersion=10.11',
            'MAILER_DSN' => 'smtp://installer_user:installer_password@localhost:587',
            'DEFAULT_EMAIL' => 'installer@test.local',
            'OAUTH_KEYCLOAK_SERVER' => 'http://dummy',
            'OAUTH_KEYCLOAK_REALM' => 'dummy',
            'OAUTH_KEYCLOAK_CLIENT_ID' => 'dummy',
            'OAUTH_KEYCLOAK_CLIENT_SECRET' => 'dummy',
        ];

        foreach ($values as $key => $value) {
            $this->envBackups[$key] = $_ENV[$key] ?? null;
            $_ENV[$key] = $value;
        }
    }
}
