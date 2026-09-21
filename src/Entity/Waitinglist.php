<?php

namespace App\Entity;

use App\Repository\WaitinglistRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WaitinglistRepository::class)]
class Waitinglist
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    /** @var User|null */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'waitinglists')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    /** @var Rooms|null */
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'waitinglists')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    /** @var \DateTimeImmutable|null */
    #[ORM\Column(type: 'datetime_immutable')]
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
