<?php

namespace App\Entity;

use App\Repository\KeycloakGroupsToServersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeycloakGroupsToServersRepository::class)]
class KeycloakGroupsToServers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'keycloakGroups', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $server;
    #[ORM\Column(type: 'text')]
    private $keycloakGroup;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getServer(): ?Server
    {
        return $this->server;
    }
    public function setServer(?Server $server): self
    {
        $this->server = $server;

        return $this;
    }
    public function getKeycloakGroup(): ?string
    {
        return $this->keycloakGroup;
    }
    public function setKeycloakGroup(string $keycloakGroup): self
    {
        $this->keycloakGroup = $keycloakGroup;

        return $this;
    }
}
