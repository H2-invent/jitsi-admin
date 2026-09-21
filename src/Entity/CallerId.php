<?php

namespace App\Entity;

use App\Repository\CallerIdRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallerIdRepository::class)]
class CallerId
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    /** @var Rooms|null */
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'callerIds')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    /** @var User|null */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'callerIds')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $callerId;
    /** @var \DateTimeImmutable|null */
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;
    /** @var CallerSession|null */
    #[ORM\OneToOne(targetEntity: CallerSession::class, inversedBy: 'caller', cascade: ['persist', 'remove'])]
    private $callerSession;
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
    public function getCallerId(): ?string
    {
        return $this->callerId;
    }
    public function setCallerId(string $callerId): self
    {
        $this->callerId = $callerId;

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
    public function getCallerSession(): ?CallerSession
    {
        return $this->callerSession;
    }
    public function setCallerSession(?CallerSession $callerSession): self
    {
        $this->callerSession = $callerSession;

        return $this;
    }
}
