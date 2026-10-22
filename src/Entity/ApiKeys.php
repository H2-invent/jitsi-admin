<?php

namespace App\Entity;

use App\Repository\ApiKeysRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiKeysRepository::class)]
class ApiKeys
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $clientId;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $clientSecret;
    /** @var \DateTimeImmutable|null */
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getClientId(): ?string
    {
        return $this->clientId;
    }
    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }
    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }
    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
