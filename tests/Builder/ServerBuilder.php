<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Entity\Server;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class ServerBuilder
{
    private Server $server;

    private function __construct()
    {
        $suffix = self::uniqueSuffix();

        $this->server = (new Server())
            ->setUrl("https://meet-{$suffix}.example.test")
            ->setSlug("server-{$suffix}")
            ->setServerName("Server {$suffix}")
            ->setJwtModeratorPosition(0)
            ->setLogoUrl('https://example.test/logo.png')
            ->setAppId('jitsiId')
            ->setAppSecret('jitsiSecret')
            ->setPrivacyPolicy('https://privacy.example.test')
            ->setIsProvisioningEnabled(false)
            ->setIsAllowedToCloneForAutoscale(false);
    }

    public static function create(): self
    {
        return new self();
    }

    public function withAdministrator(?User $administrator): self
    {
        $this->server->setAdministrator($administrator);

        return $this;
    }

    public function withUser(User $user): self
    {
        $this->server->addUser($user);

        return $this;
    }

    public function withProvisioning(bool $enabled = true, bool $allowClone = true): self
    {
        $this->server->setIsProvisioningEnabled($enabled);
        $this->server->setIsAllowedToCloneForAutoscale($allowClone);

        return $this;
    }

    public function withUrl(string $url): self
    {
        $this->server->setUrl($url);

        return $this;
    }

    public function withSlug(string $slug): self
    {
        $this->server->setSlug($slug);

        return $this;
    }

    public function withServerName(?string $serverName): self
    {
        $this->server->setServerName($serverName);

        return $this;
    }

    public function withJwtModeratorPosition(int $position): self
    {
        $this->server->setJwtModeratorPosition($position);

        return $this;
    }

    public function withLogoUrl(?string $logoUrl): self
    {
        $this->server->setLogoUrl($logoUrl);

        return $this;
    }

    public function withAppId(?string $appId): self
    {
        $this->server->setAppId($appId);

        return $this;
    }

    public function withAppSecret(?string $appSecret): self
    {
        $this->server->setAppSecret($appSecret);

        return $this;
    }

    public function withPrivacyPolicy(?string $privacyPolicy): self
    {
        $this->server->setPrivacyPolicy($privacyPolicy);

        return $this;
    }

    public function build(): Server
    {
        return $this->server;
    }

    public function persist(EntityManagerInterface $entityManager, bool $flush = true): Server
    {
        $entityManager->persist($this->server);
        if ($flush) {
            $entityManager->flush();
        }

        return $this->server;
    }

    private static function uniqueSuffix(): string
    {
        return bin2hex(random_bytes(8));
    }
}



