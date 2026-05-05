<?php

namespace App\Entity;

use App\Repository\CalloutSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalloutSessionRepository::class)]
class CalloutSession
{
    public static $STATE = [
        0 => 'INITIATED',
        10 => 'DIALED',
        15 => 'RINGING',
        20 => 'ON_HOLD',
        30 => 'OCCUPIED',
        40 => 'LATER',
        50 => 'TIMEOUT',
    ];
    public static $RINGING = 15;
    public static $TIMEOUT = 50;
    public static $LATER = 40;
    public static $OCCUPIED = 30;
    public static $ON_HOLD = 20;
    public static $DIALED = 10;
    public static $INITIATED = 0;


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'calloutSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Rooms $room = null;

    #[ORM\ManyToOne(inversedBy: 'calloutSessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $invitedFrom = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $uid = null;

    #[ORM\Column]
    private ?int $state = null;

    #[ORM\Column]
    private ?int $leftRetries = 2;

    #[ORM\Column(nullable: true)]
    private ?float $LastDialed = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getInvitedFrom(): ?User
    {
        return $this->invitedFrom;
    }

    public function setInvitedFrom(?User $invitedFrom): self
    {
        $this->invitedFrom = $invitedFrom;

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getLeftRetries(): ?int
    {
        return $this->leftRetries;
    }

    public function setLeftRetries(int $leftRetries): self
    {
        $this->leftRetries = $leftRetries;

        return $this;
    }

    public function getLastDialed(): ?float
    {
        return $this->LastDialed;
    }

    public function setLastDialed(?float $LastDialed): static
    {
        $this->LastDialed = $LastDialed;

        return $this;
    }
}
