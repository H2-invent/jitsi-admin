<?php

namespace App\Tests\Service;

use App\Entity\KeycloakGroupsToServers;
use App\Entity\Server;
use App\Repository\KeycloakGroupsToServersRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\SetupInitialService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SetupInitialServiceTest extends KernelTestCase
{
    private function service(): SetupInitialService
    {
        return self::getContainer()->get(SetupInitialService::class);
    }

    private function importData(string $email, string $url, array $groups = ['group-a', 'group-b']): array
    {
        return [
            'username' => $email,
            'server' => [
                'name' => 'Imported Server',
                'url' => $url,
                'app_id' => 'imported-app-id',
                'app_secret' => 'imported-app-secret',
                'keycloak_groups' => $groups,
                'middleware' => 'https://middleware.example.org',
            ],
        ];
    }

    public function testImportCreatesServerAndKeycloakGroupsForExistingUser(): void
    {
        self::bootKernel();
        $url = 'https://setup-' . uniqid() . '.local';
        $userRepo = self::getContainer()->get(UserRepository::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $groupRepo = self::getContainer()->get(KeycloakGroupsToServersRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $userCount = $userRepo->count(['email' => 'test@local.de']);

        $this->service()->import($this->importData('test@local.de', $url));

        self::assertSame($userCount, $userRepo->count(['email' => 'test@local.de']));
        $server = $serverRepo->findOneBy(['url' => $url, 'appId' => 'imported-app-id']);
        self::assertInstanceOf(Server::class, $server);
        self::assertSame('Imported Server', $server->getServerName());
        self::assertSame('imported-app-secret', $server->getAppSecret());
        self::assertSame('https://middleware.example.org', $server->getLivekitMiddlewareUrl());
        self::assertTrue($server->isLiveKitServer());
        self::assertSame($user, $server->getAdministrator());
        self::assertTrue($server->getUser()->contains($user));
        self::assertNotNull($server->getSlug());

        $groups = $groupRepo->findBy(['server' => $server]);
        self::assertCount(2, $groups);
        $groupNames = array_map(static fn(KeycloakGroupsToServers $g) => $g->getKeycloakGroup(), $groups);
        self::assertContains('group-a', $groupNames);
        self::assertContains('group-b', $groupNames);
    }

    public function testImportIsIdempotent(): void
    {
        self::bootKernel();
        $url = 'https://setup-' . uniqid() . '.local';
        $data = $this->importData('test@local.de', $url);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $groupRepo = self::getContainer()->get(KeycloakGroupsToServersRepository::class);

        $this->service()->import($data);
        $userCount = $userRepo->count(['email' => 'test@local.de']);
        $server = $serverRepo->findOneBy(['url' => $url]);
        self::assertNotNull($server);
        $serverCount = $serverRepo->count(['url' => $url]);
        $groupCount = $groupRepo->count(['server' => $server]);
        self::assertSame(1, $serverCount);
        self::assertSame(2, $groupCount);

        $this->service()->import($data);

        self::assertSame($userCount, $userRepo->count(['email' => 'test@local.de']));
        self::assertSame($serverCount, $serverRepo->count(['url' => $url]));
        self::assertSame($groupCount, $groupRepo->count(['server' => $server]));
    }

    public function testImportReusesExistingUserAndServer(): void
    {
        self::bootKernel();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $groupRepo = self::getContainer()->get(KeycloakGroupsToServersRepository::class);
        $existingServer = $serverRepo->findOneBy(['url' => 'meet.jit.si', 'appId' => 'jitsiId']);
        self::assertNotNull($existingServer);
        $userCount = $userRepo->count([]);
        $serverCount = $serverRepo->count([]);
        $group = 'existing-group-' . uniqid();

        $data = [
            'username' => 'test@local.de',
            'server' => [
                'name' => 'Ignored Name',
                'url' => 'meet.jit.si',
                'app_id' => 'jitsiId',
                'app_secret' => 'ignored',
                'keycloak_groups' => [$group],
                'middleware' => 'https://ignored.example.org',
            ],
        ];

        $this->service()->import($data);

        self::assertSame($userCount, $userRepo->count([]));
        self::assertSame($serverCount, $serverRepo->count([]));
        self::assertSame('Server without License', $existingServer->getServerName());
        $mapping = $groupRepo->findOneBy(['keycloakGroup' => $group, 'server' => $existingServer]);
        self::assertInstanceOf(KeycloakGroupsToServers::class, $mapping);
    }
}
