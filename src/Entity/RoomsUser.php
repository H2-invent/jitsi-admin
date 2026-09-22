<?php

namespace App\Entity;

use App\Repository\RoomsUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomsUserRepository::class)]
#[ORM\Table(name: 'userRoomsAttributes')]
class RoomsUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'roomsAttributes')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'userAttributes', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Rooms $room;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $shareDisplay = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $moderator = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $privateMessage = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $lobbyModerator = null;

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
    public function getShareDisplay(): ?bool
    {
        return $this->shareDisplay;
    }
    public function setShareDisplay(?bool $shareDisplay): self
    {
        $this->shareDisplay = $shareDisplay;

        return $this;
    }
    public function getModerator(): ?bool
    {
        return $this->moderator;
    }
    public function setModerator(?bool $moderator): self
    {
        $this->moderator = $moderator;

        return $this;
    }
    public function getPrivateMessage(): ?bool
    {
        return $this->privateMessage;
    }
    public function setPrivateMessage(?bool $privateMessage): self
    {
        $this->privateMessage = $privateMessage;

        return $this;
    }
    public function getLobbyModerator(): ?bool
    {
        return $this->lobbyModerator;
    }
    public function setLobbyModerator(?bool $lobbyModerator): self
    {
        $this->lobbyModerator = $lobbyModerator;

        return $this;
    }
}
