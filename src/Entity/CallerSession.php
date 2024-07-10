<?php

namespace App\Entity;

use App\Repository\CallerSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallerSessionRepository::class)]
class CallerSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $sessionId;
    #[ORM\OneToOne(targetEntity: LobbyWaitungUser::class, inversedBy: 'callerSession', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: true)]
    private $lobbyWaitingUser;
    #[ORM\Column(type: 'datetime')]
    private $createdAt;
    #[ORM\Column(type: 'boolean')]
    private $authOk;
    #[ORM\Column(type: 'text', nullable: true)]
    private $callerId;
    #[ORM\OneToOne(targetEntity: CallerId::class, mappedBy: 'callerSession', cascade: ['persist'])]
    private $caller;
    #[ORM\Column(type: 'text', nullable: true)]
    private $showName;
    #[ORM\Column(type: 'boolean')]
    private $callerIdVerified = false;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $forceFinish;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $messageUid = null;

    #[ORM\Column(length: 3000, nullable: true)]
    private ?string $messageText = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSipVideoUser = false;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }
    public function setSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }
    public function getLobbyWaitingUser(): ?LobbyWaitungUser
    {
        return $this->lobbyWaitingUser;
    }
    public function setLobbyWaitingUser(?LobbyWaitungUser $lobbyWaitingUser): self
    {
        $this->lobbyWaitingUser = $lobbyWaitingUser;

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
    public function getAuthOk(): ?bool
    {
        return $this->authOk;
    }
    public function setAuthOk(bool $authOk): self
    {
        $this->authOk = $authOk;

        return $this;
    }
    public function getCallerId(): ?string
    {
        return $this->callerId;
    }
    public function setCallerId(?string $callerId): self
    {
        $this->callerId = $callerId;

        return $this;
    }
    public function getCaller(): ?CallerId
    {
        return $this->caller;
    }
    public function setCaller(?CallerId $caller): self
    {
        // unset the owning side of the relation if necessary
        if ($caller === null && $this->caller !== null) {
            $this->caller->setCallerSession(null);
        }

        // set the owning side of the relation if necessary
        if ($caller !== null && $caller->getCallerSession() !== $this) {
            $caller->setCallerSession($this);
        }

        $this->caller = $caller;

        return $this;
    }
    public function getShowName(): ?string
    {
        return $this->showName;
    }
    public function setShowName(?string $showName): self
    {
        $this->showName = $showName;

        return $this;
    }
    public function getCallerIdVerified(): ?bool
    {
        return $this->callerIdVerified;
    }
    public function setCallerIdVerified(bool $callerIdVerified): self
    {
        $this->callerIdVerified = $callerIdVerified;

        return $this;
    }
    public function getForceFinish(): ?bool
    {
        return $this->forceFinish;
    }
    public function setForceFinish(?bool $forceFinish): self
    {
        $this->forceFinish = $forceFinish;

        return $this;
    }

    public function getMessageUid(): ?string
    {
        return $this->messageUid;
    }

    public function setMessageUid(?string $messageUid): self
    {
        $this->messageUid = $messageUid;

        return $this;
    }

    public function getMessageText(): ?string
    {
        return $this->messageText;
    }

    public function setMessageText(?string $messageText): self
    {
        $this->messageText = $messageText;

        return $this;
    }

    public function isIsSipVideoUser(): ?bool
    {
        return $this->isSipVideoUser;
    }

    public function setIsSipVideoUser(?bool $isSipVideoUser): static
    {
        $this->isSipVideoUser = $isSipVideoUser;

        return $this;
    }
}
