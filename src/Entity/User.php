<?php
// src/Entity/User.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\UserBase as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UserRepository;

/**
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass=UserRepository::class)
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

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="addressbookInverse")
     */
    private $addressbook;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="addressbook")
     */
    private $addressbookInverse;

    /**
     * @ORM\OneToMany(targetEntity=RoomsUser::class, mappedBy="user")
     */
    private $roomsAttributes;

    /**
     * @ORM\OneToMany(targetEntity=Subscriber::class, mappedBy="user")
     */
    private $subscribers;

    /**
     * @ORM\Column(type="array", nullable=true,name="keycloakGroup")
     */
    private $groups = [];

    /**
     * @ORM\OneToMany(targetEntity=SchedulingTimeUser::class, mappedBy="user")
     */
    private $schedulingTimeUsers;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $uid;

    /**
     * @ORM\OneToMany(targetEntity=Waitinglist::class, mappedBy="user")
     */
    private $waitinglists;



    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->servers = new ArrayCollection();
        $this->roomModerator = new ArrayCollection();
        $this->serverAdmins = new ArrayCollection();
        $this->addressbook = new ArrayCollection();
        $this->addressbookInverse = new ArrayCollection();
        $this->roomsAttributes = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
        $this->schedulingTimeUsers = new ArrayCollection();
        $this->waitinglists = new ArrayCollection();

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

    /**
     * @return Collection|self[]
     */
    public function getAddressbook(): Collection
    {
        return $this->addressbook;
    }

    public function addAddressbook(self $addressbook): self
    {
        if (!$this->addressbook->contains($addressbook)) {
            $this->addressbook[] = $addressbook;
        }

        return $this;
    }

    public function removeAddressbook(self $addressbook): self
    {
        $this->addressbook->removeElement($addressbook);

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getAddressbookInverse(): Collection
    {
        return $this->addressbookInverse;
    }

    public function addAddressbookInverse(self $addressbookInverse): self
    {
        if (!$this->addressbookInverse->contains($addressbookInverse)) {
            $this->addressbookInverse[] = $addressbookInverse;
            $addressbookInverse->addAddressbook($this);
        }

        return $this;
    }

    public function removeAddressbookInverse(self $addressbookInverse): self
    {
        if ($this->addressbookInverse->removeElement($addressbookInverse)) {
            $addressbookInverse->removeAddressbook($this);
        }

        return $this;
    }

    /**
     * @return Collection|RoomsUser[]
     */
    public function getRoomsAttributes(): Collection
    {
        return $this->roomsAttributes;
    }

    public function addRoomsAttributes(RoomsUser $roomsNew): self
    {
        if (!$this->roomsAttributes->contains($roomsNew)) {
            $this->roomsAttributes[] = $roomsNew;
            $roomsNew->setUser($this);
        }

        return $this;
    }

    public function removeRoomsAttributes(RoomsUser $roomsNew): self
    {
        if ($this->roomsAttributes->removeElement($roomsNew)) {
            // set the owning side to null (unless already changed)
            if ($roomsNew->getUser() === $this) {
                $roomsNew->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Subscriber[]
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function addSubscriber(Subscriber $subscriber): self
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers[] = $subscriber;
            $subscriber->setUser($this);
        }

        return $this;
    }

    public function removeSubscriber(Subscriber $subscriber): self
    {
        if ($this->subscribers->removeElement($subscriber)) {
            // set the owning side to null (unless already changed)
            if ($subscriber->getUser() === $this) {
                $subscriber->setUser(null);
            }
        }

        return $this;
    }

    public function getGroups(): ?array
    {
        return $this->groups;
    }

    public function setGroups(?array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @return Collection|SchedulingTimeUser[]
     */
    public function getSchedulingTimeUsers(): Collection
    {
        return $this->schedulingTimeUsers;
    }

    public function addSchedulingTimeUser(SchedulingTimeUser $schedulingTimeUser): self
    {
        if (!$this->schedulingTimeUsers->contains($schedulingTimeUser)) {
            $this->schedulingTimeUsers[] = $schedulingTimeUser;
            $schedulingTimeUser->setUser($this);
        }

        return $this;
    }

    public function removeSchedulingTimeUser(SchedulingTimeUser $schedulingTimeUser): self
    {
        if ($this->schedulingTimeUsers->removeElement($schedulingTimeUser)) {
            // set the owning side to null (unless already changed)
            if ($schedulingTimeUser->getUser() === $this) {
                $schedulingTimeUser->setUser(null);
            }
        }

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return Collection|Waitinglist[]
     */
    public function getWaitinglists(): Collection
    {
        return $this->waitinglists;
    }

    public function addWaitinglist(Waitinglist $waitinglist): self
    {
        if (!$this->waitinglists->contains($waitinglist)) {
            $this->waitinglists[] = $waitinglist;
            $waitinglist->setUser($this);
        }

        return $this;
    }

    public function removeWaitinglist(Waitinglist $waitinglist): self
    {
        if ($this->waitinglists->removeElement($waitinglist)) {
            // set the owning side to null (unless already changed)
            if ($waitinglist->getUser() === $this) {
                $waitinglist->setUser(null);
            }
        }

        return $this;
    }



}
