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
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $callerId;

    #[ORM\OneToOne(targetEntity: Rooms::class, inversedBy: 'callerRoom')]
    #[ORM\JoinColumn(nullable: false)]
    private Rooms $room;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

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
