<?php

namespace App\Entity;

use App\Repository\LobbyWaitungUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LobbyWaitungUserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LobbyWaitungUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'lobbyWaitungUsers')]
    #[ORM\JoinColumn(nullable: true)]
    private $user;
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'lobbyWaitungUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    #[ORM\Column(type: 'datetime')]
    private $createdAt;
    #[ORM\Column(type: 'text')]
    private $uid;
    #[ORM\Column(type: 'string', length: 5)]
    private $type;
    #[ORM\Column(type: 'text')]
    private $showName;
    #[ORM\OneToOne(targetEntity: CallerSession::class, mappedBy: 'lobbyWaitingUser', cascade: ['persist'])]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private $callerSession;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $closeBrowser;

    #[ORM\Column(nullable: true)]
    private ?bool $websocketReady = false;
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
    public function getUid(): ?string
    {
        return $this->uid;
    }
    public function setUid(string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }
    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
    public function getShowName(): ?string
    {
        return $this->showName;
    }
    public function setShowName(string $showName): self
    {
        $this->showName = $showName;

        return $this;
    }
    public function getCallerSession(): ?CallerSession
    {
        return $this->callerSession;
    }
    public function setCallerSession(CallerSession $callerSession): self
    {
        // set the owning side of the relation if necessary
        if ($callerSession->getLobbyWaitingUser() !== $this) {
            $callerSession->setLobbyWaitingUser($this);
        }

        $this->callerSession = $callerSession;

        return $this;
    }
    public function getCloseBrowser(): ?bool
    {
        return $this->closeBrowser;
    }
    public function setCloseBrowser(?bool $closeBrowser): self
    {
        $this->closeBrowser = $closeBrowser;

        return $this;
    }

    public function isWebsocketReady(): ?bool
    {
        return $this->websocketReady;
    }

    public function setWebsocketReady(?bool $websocketReady): self
    {
        $this->websocketReady = $websocketReady;

        return $this;
    }
}
