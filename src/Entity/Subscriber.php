<?php

namespace App\Entity;

use App\Repository\SubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriberRepository::class)]
class Subscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'subscribers')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'subscribers')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    #[ORM\Column(type: 'text')]
    private $uid;
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
    public function getUid(): ?string
    {
        return $this->uid;
    }
    public function setUid(string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }
}
