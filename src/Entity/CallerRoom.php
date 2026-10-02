<?php

namespace App\Entity;

use App\Repository\CallerRoomRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallerRoomRepository::class)]
class CallerRoom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $callerId;
    #[ORM\OneToOne(targetEntity: Rooms::class, inversedBy: 'callerRoom')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCallerId(): ?string
    {
        return $this->callerId;
    }
    public function setCallerId(string $callerId): self
    {
        $this->callerId = $callerId;

        return $this;
    }
    public function getRoom(): ?Rooms
    {
        return $this->room;
    }
    public function setRoom(Rooms $room): self
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
