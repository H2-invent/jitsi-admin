<?php

namespace App\Entity;

use App\Repository\CallerSessionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CallerSessionRepository::class)
 */
class CallerSession
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
    private $sessionId;

    /**
     * @ORM\OneToOne(targetEntity=LobbyWaitungUser::class, inversedBy="callerSession", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $lobbyWaitingUser;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $authOk;

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
}
