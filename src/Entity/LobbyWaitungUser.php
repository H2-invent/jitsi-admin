<?php

namespace App\Entity;

use App\Repository\LobbyWaitungUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LobbyWaitungUserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LobbyWaitungUser
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    /** @var User|null */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'lobbyWaitungUsers')]
    #[ORM\JoinColumn(nullable: true)]
    private $user;
    /** @var Rooms|null */
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'lobbyWaitungUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    /** @var \DateTimeImmutable|null */
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $uid;
    /** @var string|null */
    #[ORM\Column(type: 'string', length: 5)]
    private $type;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $showName;
    /** @var CallerSession|null */
    #[ORM\OneToOne(targetEntity: CallerSession::class, mappedBy: 'lobbyWaitingUser', cascade: ['persist'])]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private $callerSession;
    /** @var bool|null */
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
