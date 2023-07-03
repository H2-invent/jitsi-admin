<?php

namespace App\Entity;

use App\Repository\DeputyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeputyRepository::class)]
class Deputy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'deputiesElement')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $deputy = null;

    #[ORM\ManyToOne(inversedBy: 'managerElement')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $manager = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isFromLdap = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeputy(): ?User
    {
        return $this->deputy;
    }

    public function setDeputy(?User $deputy): self
    {
        $this->deputy = $deputy;

        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    public function isIsFromLdap(): ?bool
    {
        return $this->isFromLdap;
    }

    public function setIsFromLdap(?bool $isFromLdap): self
    {
        $this->isFromLdap = $isFromLdap;

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
