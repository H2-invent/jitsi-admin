<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class UserBuilder
{
    private User $user;

    private function __construct()
    {
        $suffix = self::uniqueSuffix();
        $email = "user+{$suffix}@example.test";

        $this->user = (new User())
            ->setEmail($email)
            ->setUsername($email)
            ->setUuid("uuid_{$suffix}")
            ->setUid("uid_{$suffix}")
            ->setPassword('test-password-not-used-for-loginUser')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setFirstName('Test')
            ->setLastName("User {$suffix}")
            ->setRegisterId('123456')
            ->setKeycloakId("keycloak_{$suffix}")
            ->setSpezialProperties(['ou' => 'Test', 'departmentNumber' => '1234'])
            ->setTimeZone('Europe/Berlin')
            ->setIndexer(strtolower($email . ' ' . $email . ' test user'))
            ->setRoles(['ROLE_USER']);
    }

    public static function create(): self
    {
        return new self();
    }

    public function withEmail(string $email): self
    {
        $this->user->setEmail($email);
        $this->user->setIndexer(strtolower($email . ' ' . ($this->user->getUsername() ?? $email)));

        return $this;
    }

    public function withUsername(string $username): self
    {
        $this->user->setUsername($username);
        $this->user->setIndexer(strtolower(($this->user->getEmail() ?? $username) . ' ' . $username));

        return $this;
    }

    public function withUuid(string $uuid): self
    {
        $this->user->setUuid($uuid);

        return $this;
    }

    public function withUid(?string $uid): self
    {
        $this->user->setUid($uid);

        return $this;
    }

    public function withRoles(array $roles): self
    {
        $this->user->setRoles($roles);

        return $this;
    }

    public function withName(?string $firstName, ?string $lastName): self
    {
        $this->user->setFirstName($firstName);
        $this->user->setLastName($lastName);

        return $this;
    }

    public function withRegisterId(?string $registerId): self
    {
        $this->user->setRegisterId($registerId);

        return $this;
    }

    public function withKeycloakId(?string $keycloakId): self
    {
        $this->user->setKeycloakId($keycloakId);

        return $this;
    }

    public function withSpezialProperties(?array $spezialProperties): self
    {
        $this->user->setSpezialProperties($spezialProperties);

        return $this;
    }

    public function withTimeZone(?string $timeZone): self
    {
        $this->user->setTimeZone($timeZone);

        return $this;
    }

    public function withIndexer(?string $indexer): self
    {
        $this->user->setIndexer($indexer);

        return $this;
    }

    public function build(): User
    {
        return $this->user;
    }

    public function persist(EntityManagerInterface $entityManager, bool $flush = true): User
    {
        $entityManager->persist($this->user);
        if ($flush) {
            $entityManager->flush();
        }

        return $this->user;
    }

    private static function uniqueSuffix(): string
    {
        return bin2hex(random_bytes(8));
    }
}



