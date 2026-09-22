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
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'lobbyWaitungUsers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'lobbyWaitungUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private Rooms $room;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'text')]
    private string $uid;

    #[ORM\Column(type: 'string', length: 5)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $showName;

    #[ORM\OneToOne(targetEntity: CallerSession::class, mappedBy: 'lobbyWaitingUser', cascade: ['persist'])]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?CallerSession $callerSession = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $closeBrowser = null;

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
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
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
