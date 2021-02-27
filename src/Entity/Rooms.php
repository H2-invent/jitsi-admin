<?php

namespace App\Entity;

use App\Repository\RoomsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoomsRepository::class)
 */
class Rooms
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     */
    private $start;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $enddate;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="rooms")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Server::class, inversedBy="rooms")
     * @ORM\JoinColumn(nullable=false)
     */
    private $server;

    /**
     * @ORM\Column(type="text")
     */
    private $uid;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="roomModerator")
     * @ORM\JoinColumn(nullable=true)
     */
    private $moderator;

    /**
     * @ORM\Column(type="float")
     */
    private $duration;

    /**
     * @ORM\Column(type="integer")
     */
    private $sequence;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    private $uidReal;

    /**
     * @ORM\Column(type="boolean")
     */
    private $onlyRegisteredUsers= false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $agenda;

    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStart(): ?\DateTimeInterface
    {
        return $this->start;
    }

    public function setStart(\DateTimeInterface $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnddate(): ?\DateTimeInterface
    {
        return $this->enddate;
    }

    public function setEnddate(?\DateTimeInterface $enddate): self
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user[] = $user;
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        $this->user->removeElement($user);

        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): self
    {
        $this->server = $server;

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

    public function getModerator(): ?User
    {
        return $this->moderator;
    }

    public function setModerator(?User $moderator): self
    {
        $this->moderator = $moderator;

        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getUidReal(): ?string
    {
        return $this->uidReal;
    }

    public function setUidReal(string $uidReal): self
    {
        $this->uidReal = $uidReal;

        return $this;
    }

    public function getOnlyRegisteredUsers(): ?bool
    {
        return $this->onlyRegisteredUsers;
    }

    public function setOnlyRegisteredUsers(bool $onlyRegisteredUsers): self
    {
        $this->onlyRegisteredUsers = $onlyRegisteredUsers;

        return $this;
    }

    public function getAgenda(): ?string
    {
        return $this->agenda;
    }

    public function setAgenda(?string $agenda): self
    {
        $this->agenda = $agenda;

        return $this;
    }
}
