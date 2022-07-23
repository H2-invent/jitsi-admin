<?php

namespace App\Entity;

use App\Repository\WaitinglistRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WaitinglistRepository::class)]
class Waitinglist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'waitinglists')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'waitinglists')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    #[ORM\Column(type: 'datetime')]
    private $createdAt;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getRoom(): ?Rooms
    {
        return $this->room;
    }
    public function setRoom(?Rooms $room): self
    {
        $this->room = $room;

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
