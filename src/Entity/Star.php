<?php

namespace App\Entity;

use App\Repository\StarRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StarRepository::class)]
class Star
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'stars')]
    #[ORM\JoinColumn(nullable: false)]
    private $server;
    #[ORM\Column(type: 'integer')]
    private $star;
    #[ORM\Column(type: 'text', nullable: true)]
    private $comment;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $browser = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $os = null;
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
    public function getStar(): ?int
    {
        return $this->star;
    }
    public function setStar(int $star): self
    {
        $this->star = $star;

        return $this;
    }
    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    public function getOs(): ?string
    {
        return $this->os;
    }

    public function setOs(?string $os): self
    {
        $this->os = $os;

        return $this;
    }
}
