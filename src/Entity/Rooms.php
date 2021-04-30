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
     * @ORM\Column(type="datetime", nullable=true)
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

    /**
     * @ORM\OneToMany(targetEntity=RoomsUser::class, mappedBy="room")
     */
    private $userAttributes;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $dissallowScreenshareGlobal;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $dissallowPrivateMessage;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $public;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $showRoomOnJoinpage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $uidParticipant;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $uidModerator;

    /**
     * @ORM\OneToMany(targetEntity=Subscriber::class, mappedBy="room")
     */
    private $subscribers;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $maxParticipants;

    /**
     * @ORM\OneToMany(targetEntity=Scheduling::class, mappedBy="room")
     */
    private $schedulings;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $scheduleMeeting;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $waitinglist;

    /**
     * @ORM\OneToMany(targetEntity=Waitinglist::class, mappedBy="room")
     */
    private $waitinglists;

    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->userAttributes = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
        $this->schedulings = new ArrayCollection();
        $this->waitinglists = new ArrayCollection();
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

    public function setStart(?\DateTimeInterface $start): self
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

    /**
     * @return Collection|RoomsUser[]
     */
    public function getUserAttributes(): Collection
    {
        return $this->userAttributes;
    }

    public function addUserAttribute(RoomsUser $userAttribute): self
    {
        if (!$this->userAttributes->contains($userAttribute)) {
            $this->userAttributes[] = $userAttribute;
            $userAttribute->setRoom($this);
        }

        return $this;
    }

    public function removeUserAttribute(RoomsUser $userAttribute): self
    {
        if ($this->userAttributes->removeElement($userAttribute)) {
            // set the owning side to null (unless already changed)
            if ($userAttribute->getRoom() === $this) {
                $userAttribute->setRoom(null);
            }
        }

        return $this;
    }

    public function getDissallowScreenshareGlobal(): ?bool
    {
        return $this->dissallowScreenshareGlobal;
    }

    public function setDissallowScreenshareGlobal(?bool $allowScreenshareGlobal): self
    {
        $this->dissallowScreenshareGlobal = $allowScreenshareGlobal;

        return $this;
    }

    public function getDissallowPrivateMessage(): ?bool
    {
        return $this->dissallowPrivateMessage;
    }

    public function setDissallowPrivateMessage(?bool $dissallowPrivateMessage): self
    {
        $this->dissallowPrivateMessage = $dissallowPrivateMessage;

        return $this;
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): self
    {
        $this->public = $public;

        return $this;
    }

    public function getShowRoomOnJoinpage(): ?bool
    {
        return $this->showRoomOnJoinpage;
    }

    public function setShowRoomOnJoinpage(?bool $showRoomOnJoinpage): self
    {
        $this->showRoomOnJoinpage = $showRoomOnJoinpage;

        return $this;
    }

    public function getUidParticipant(): ?string
    {
        return $this->uidParticipant;
    }

    public function setUidParticipant(?string $uidParticipant): self
    {
        $this->uidParticipant = $uidParticipant;

        return $this;
    }

    public function getUidModerator(): ?string
    {
        return $this->uidModerator;
    }

    public function setUidModerator(?string $uidModerator): self
    {
        $this->uidModerator = $uidModerator;

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
            $subscriber->setRoom($this);
        }

        return $this;
    }

    public function removeSubscriber(Subscriber $subscriber): self
    {
        if ($this->subscribers->removeElement($subscriber)) {
            // set the owning side to null (unless already changed)
            if ($subscriber->getRoom() === $this) {
                $subscriber->setRoom(null);
            }
        }

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): self
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    /**
     * @return Collection|Scheduling[]
     */
    public function getSchedulings(): Collection
    {
        return $this->schedulings;
    }

    public function addScheduling(Scheduling $scheduling): self
    {
        if (!$this->schedulings->contains($scheduling)) {
            $this->schedulings[] = $scheduling;
            $scheduling->setRoom($this);
        }

        return $this;
    }

    public function removeScheduling(Scheduling $scheduling): self
    {
        if ($this->schedulings->removeElement($scheduling)) {
            // set the owning side to null (unless already changed)
            if ($scheduling->getRoom() === $this) {
                $scheduling->setRoom(null);
            }
        }

        return $this;
    }

    public function getScheduleMeeting(): ?bool
    {
        return $this->scheduleMeeting;
    }

    public function setScheduleMeeting(?bool $scheduleMeeting): self
    {
        $this->scheduleMeeting = $scheduleMeeting;

        return $this;
    }

    public function getWaitinglist(): ?bool
    {
        return $this->waitinglist;
    }

    public function setWaitinglist(?bool $waitinglist): self
    {
        $this->waitinglist = $waitinglist;

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
            $waitinglist->setRoom($this);
        }

        return $this;
    }

    public function removeWaitinglist(Waitinglist $waitinglist): self
    {
        if ($this->waitinglists->removeElement($waitinglist)) {
            // set the owning side to null (unless already changed)
            if ($waitinglist->getRoom() === $this) {
                $waitinglist->setRoom(null);
            }
        }

        return $this;
    }
}
