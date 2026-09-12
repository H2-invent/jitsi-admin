<?php

namespace App\Tests\Service\api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\api\KeycloakService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KeycloakServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private KeycloakService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->service = new KeycloakService($this->entityManager);
    }

    public function testGetUserByEmail(): void
    {
        $user = $this->service->getUSer('test@local.de');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('test@local.de', $user->getEmail());
    }

    public function testGetUserByKeycloakIdTakesPrecedenceOverEmail(): void
    {
        $keycloakUser = (new User())
            ->setEmail('keycloak-precedence@local.de')
            ->setUsername('keycloak-precedence@local.de')
            ->setUid('keycloak-precedence-uid')
            ->setKeycloakId('kc-unique-98765')
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($keycloakUser);
        $this->entityManager->flush();

        $user = $this->service->getUSer('test@local.de', 'kc-unique-98765');

        self::assertSame($keycloakUser->getId(), $user->getId());
        self::assertSame('keycloak-precedence@local.de', $user->getEmail());
    }

    public function testGetUserFallsBackToEmailWhenKeycloakIdIsUnknown(): void
    {
        $user = $this->service->getUSer('test@local.de', 'kc-does-not-exist');

        self::assertInstanceOf(User::class, $user);
        self::assertSame('test@local.de', $user->getEmail());
    }

    public function testGetUserReturnsNullWhenNoUserMatches(): void
    {
        self::assertNull($this->service->getUSer('nobody@local.de'));
    }
}
