<?php

namespace App\Entity;

use App\Repository\RoomsUserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoomsUserRepository::class )
 * @ORM\Table(name="userRoomsAttributes")
 */
class RoomsUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="roomsNew")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Rooms::class, inversedBy="userAttributes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $room;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $shareDisplay;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $moderator;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $privateMessage;



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


}
    