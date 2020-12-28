<?php
// src/Entity/User.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\UserBase as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\NotBlank(message="fos_user.password.blank", groups={"Registration", "ResetPassword", "ChangePassword"})
     * @Assert\Length(min=8,
     *     minMessage="fos_user.password.short",
     *     groups={"Registration", "Profile", "ResetPassword", "ChangePassword"})
     */
    protected $plainPassword;

    /**
     * @ORM\Column(type="text")
     */
    private $email;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $keycloakId;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $registerId;

    /**
     * @ORM\ManyToMany(targetEntity=Rooms::class, mappedBy="user")
     */
    private $rooms;

    /**
     * @ORM\ManyToMany(targetEntity=Server::class, mappedBy="user")
     */
    private $servers;

    /**
     * @ORM\OneToMany(targetEntity=Rooms::class, mappedBy="moderator")
     */
    private $roomModerator;

    /**
     * @ORM\OneToMany(targetEntity=Server::class, mappedBy="administrator")
     */
    private $serverAdmins;


    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->servers = new ArrayCollection();
        $this->roomModerator = new ArrayCollection();
        $this->serverAdmins = new ArrayCollection();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getKeycloakId(): ?string
    {
        return $this->keycloakId;
    }

    public function setKeycloakId(?string $keycloakId): self
    {
        $this->keycloakId = $keycloakId;

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

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getRegisterId(): ?string
    {
        return $this->registerId;
    }

    public function setRegisterId(?string $registerId): self
    {
        $this->registerId = $registerId;

        return $this;
    }

    /**
     * @return Collection|Rooms[]
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Rooms $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms[] = $room;
            $room->addUser($this);
        }

        return $this;
    }

    public function removeRoom(Rooms $room): self
    {
        if ($this->rooms->removeElement($room)) {
            $room->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Server[]
     */
    public function getServers(): Collection
    {
        return $this->servers;
    }

    public function addServer(Server $server): self
    {
        if (!$this->servers->contains($server)) {
            $this->servers[] = $server;
            $server->addUser($this);
        }

        return $this;
    }

    public function removeServer(Server $server): self
    {
        if ($this->servers->removeElement($server)) {
            $server->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Rooms[]
     */
    public function getRoomModerator(): Collection
    {
        return $this->roomModerator;
    }

    public function addRoomModerator(Rooms $roomModerator): self
    {
        if (!$this->roomModerator->contains($roomModerator)) {
            $this->roomModerator[] = $roomModerator;
            $roomModerator->setModerator($this);
        }

        return $this;
    }

    public function removeRoomModerator(Rooms $roomModerator): self
    {
        if ($this->roomModerator->removeElement($roomModerator)) {
            // set the owning side to null (unless already changed)
            if ($roomModerator->getModerator() === $this) {
                $roomModerator->setModerator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Server[]
     */
    public function getServerAdmins(): Collection
    {
        return $this->serverAdmins;
    }

    public function addServerAdmin(Server $serverAdmin): self
    {
        if (!$this->serverAdmins->contains($serverAdmin)) {
            $this->serverAdmins[] = $serverAdmin;
            $serverAdmin->setAdministrator($this);
        }

        return $this;
    }

    public function removeServerAdmin(Server $serverAdmin): self
    {
        if ($this->serverAdmins->removeElement($serverAdmin)) {
            // set the owning side to null (unless already changed)
            if ($serverAdmin->getAdministrator() === $this) {
                $serverAdmin->setAdministrator(null);
            }
        }

        return $this;
    }

}
