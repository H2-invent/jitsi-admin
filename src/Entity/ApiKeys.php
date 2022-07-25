<?php

namespace App\Entity;

use App\Repository\ApiKeysRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiKeysRepository::class)]
class ApiKeys
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $clientId;
    #[ORM\Column(type: 'text')]
    private $clientSecret;
    #[ORM\Column(type: 'datetime')]
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
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
