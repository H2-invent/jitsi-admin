<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\KeycloakGroupsToServers;
use App\Entity\Server;
use App\Entity\User;
use App\Repository\KeycloakGroupsToServersRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SetupInitialService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserCreatorService $userCreatorService,
        private ServerRepository $serverRepository,
        private ServerService $serverService,
        private KeycloakGroupsToServersRepository $keycloakGroupsToServersRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    /**
     * @param array{
     *     username: string,
     *     server: array{
     *         name: string,
     *         url: string,
     *         app_id: string,
     *         app_secret: string,
     *         keycloak_groups: list<string>,
     *         middleware: string
     *     }
     * } $data
     */
    public function import(array $data): void
    {
        $this->entityManager->wrapInTransaction(function () use ($data) {
            $user = $this->importUser($data['username']);
            $server = $this->importServer($data['server'], $user);
            $this->importKeycloakGroups($data['server']['keycloak_groups'], $server);
        });
    }

    private function importUser(string $email): User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if ($user !== null) {
            return $user;
        }

        return $this->userCreatorService->createUser($email, null);
    }

    /**
     * @param array{
     *     name: string,
     *     url: string,
     *     app_id: string,
     *     app_secret: string,
     *     keycloak_groups: list<string>,
     *     middleware: string
     * } $data
     */
    private function importServer(array $data, User $user): Server
    {
        $server = $this->serverRepository->findOneBy(['url' => $data['url'], 'appId' => $data['app_id']]);
        if ($server !== null) {
            return $server;
        }

        $server = (new Server())
            ->setServerName($data['name'])
            ->setSlug($this->serverService->makeSlug($data['url']))
            ->setUrl($data['url'])
            ->setAppId($data['app_id'])
            ->setAppSecret($data['app_secret'])
            ->setLiveKitServer(true)
            ->setLivekitMiddlewareUrl($data['middleware'])
            ->setJwtModeratorPosition(0)
            ->setAdministrator($user)
        ;
        $this->entityManager->persist($server);
        $this->entityManager->flush();

        return $server;
    }

    /**
     * @param string[] $groups
     */
    private function importKeycloakGroups(array $groups, Server $server): void
    {
        foreach ($groups as $group) {
            $entity = $this->keycloakGroupsToServersRepository->findOneBy([
                'keycloakGroup' => $group,
                'server' => $server
            ]);
            if ($entity !== null) {
                continue;
            }
            $entity = (new KeycloakGroupsToServers())
                ->setKeycloakGroup($group)
                ->setServer($server)
            ;
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }
}
