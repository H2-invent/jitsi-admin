<?php

namespace App\Tests\Command;

use App\Command\SetupInitialCommand;
use App\Repository\KeycloakGroupsToServersRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\SetupInitialService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SetupInitialCommandTest extends KernelTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'setup-initial-' . uniqid();
        (new Filesystem())->mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            (new Filesystem())->remove($this->directory);
        }
        parent::tearDown();
    }

    private function createCommand(): SetupInitialCommand
    {
        return new SetupInitialCommand(
            $this->directory,
            self::getContainer()->get(Filesystem::class),
            self::getContainer()->get(ValidatorInterface::class),
            self::getContainer()->get(SetupInitialService::class)
        );
    }

    private function writeSetupFile(string $content): void
    {
        (new Filesystem())->dumpFile($this->directory . DIRECTORY_SEPARATOR . 'initial-setup.json', $content);
    }

    public function testMissingSetupFile(): void
    {
        self::bootKernel();
        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Could not find setup file at', $tester->getDisplay());
    }

    public function testInvalidJsonReturnsFailure(): void
    {
        self::bootKernel();
        $this->writeSetupFile('{this is not json');
        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Could not read JSON:', $tester->getDisplay());
    }

    public function testInvalidStructureReturnsFailure(): void
    {
        self::bootKernel();
        $this->writeSetupFile(json_encode(['username' => 'not-an-email', 'server' => []]));
        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('JSON structure is invalid:', $display);
        $this->assertStringContainsString('username', $display);
    }

    public function testValidSetupImportsUserServerAndGroups(): void
    {
        self::bootKernel();
        $this->writeSetupFile(json_encode([
            'username' => 'setup-initial@local.de',
            'server' => [
                'name' => 'Initial Server',
                'url' => 'https://initial.example.com',
                'app_id' => 'initialAppId',
                'app_secret' => 'initialAppSecret',
                'keycloak_groups' => ['group-a', 'group-b'],
                'middleware' => 'https://middleware.example.com',
            ],
        ]));
        foreach (self::getContainer()->get(UserRepository::class)->findAll() as $existingUser) {
            if ($existingUser->getUsername() === null) {
                $existingUser->setUsername('existing-' . $existingUser->getId());
            }
        }
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Successfully imported user and server!', $tester->getDisplay());

        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'setup-initial@local.de']);
        $this->assertNotNull($user);

        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'https://initial.example.com', 'appId' => 'initialAppId']);
        $this->assertNotNull($server);
        $this->assertSame('Initial Server', $server->getServerName());
        $this->assertSame('initialAppSecret', $server->getAppSecret());
        $this->assertSame('https://middleware.example.com', $server->getLivekitMiddlewareUrl());
        $this->assertSame($user->getId(), $server->getAdministrator()->getId());

        $groups = self::getContainer()->get(KeycloakGroupsToServersRepository::class)->findBy(['server' => $server]);
        $this->assertCount(2, $groups);
        $groupNames = array_map(static fn($group) => $group->getKeycloakGroup(), $groups);
        $this->assertContains('group-a', $groupNames);
        $this->assertContains('group-b', $groupNames);
    }
}
