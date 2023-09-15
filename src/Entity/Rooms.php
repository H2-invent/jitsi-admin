<?php

namespace App\Entity;

use App\Repository\RoomsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: RoomsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Rooms
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $name;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $start;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $enddate;
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'rooms')]
    #[Ignore]
    private $user;
    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false)]
    private $server;
    #[ORM\Column(type: 'text')]
    private $uid;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'roomModerator')]
    #[ORM\JoinColumn(nullable: true)]
    #[Ignore]
    private $moderator;
    #[ORM\Column(type: 'float')]
    private $duration;
    #[ORM\Column(type: 'integer')]
    private $sequence;
    #[ORM\Column(type: 'text', nullable: true)]
    private $uidReal;
    #[ORM\Column(type: 'boolean')]
    private $onlyRegisteredUsers = false;
    #[ORM\Column(type: 'text', nullable: true)]
    private $agenda;
    #[ORM\OneToMany(targetEntity: RoomsUser::class, mappedBy: 'room', cascade: ['persist'], orphanRemoval: true)]
    #[Ignore]
    private $userAttributes;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $dissallowScreenshareGlobal;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $dissallowPrivateMessage;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $public = true;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $showRoomOnJoinpage;
    #[ORM\Column(type: 'text', nullable: true)]
    private $uidParticipant;
    #[ORM\Column(type: 'text', nullable: true)]
    private $uidModerator;
    #[ORM\OneToMany(targetEntity: Subscriber::class, mappedBy: 'room', cascade: ['persist', 'remove'])]
    #[Ignore]
    private $subscribers;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $maxParticipants;
    #[ORM\OneToMany(targetEntity: Scheduling::class, mappedBy: 'room')]
    #[Ignore]
    private $schedulings;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $scheduleMeeting;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $waitinglist;
    #[ORM\OneToMany(targetEntity: Waitinglist::class, mappedBy: 'room', cascade: ['persist', 'remove'])]
    #[Ignore]
    private $waitinglists;
    #[ORM\ManyToOne(targetEntity: Repeat::class, inversedBy: 'rooms')]
    private $repeater;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $repeaterRemoved;
    #[ORM\OneToOne(targetEntity: Repeat::class, mappedBy: 'prototyp', cascade: ['persist', 'remove'])]
    #[Ignore]
    private $repeaterProtoype;
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'protoypeRooms')]
    #[ORM\JoinTable(name: 'prototype_users')]
    private $prototypeUsers;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $persistantRoom;
    #[ORM\Column(type: 'text', nullable: true)]
    private $slug;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $totalOpenRooms;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $totalOpenRoomsOpenTime = 30;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $timeZone;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $startUtc;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $endDateUtc;
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favorites')]
    private $favoriteUsers;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $lobby;
    #[ORM\OneToMany(targetEntity: LobbyWaitungUser::class, mappedBy: 'room', orphanRemoval: true)]
    #[Ignore]
    private $lobbyWaitungUsers;
    #[ORM\OneToMany(targetEntity: RoomStatus::class, mappedBy: 'room', orphanRemoval: true)]
    #[Ignore]
    private $roomstatuses;
    #[ORM\OneToOne(targetEntity: CallerRoom::class, mappedBy: 'room', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Ignore]
    private $callerRoom;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $startTimestamp;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $endTimestamp;
    #[ORM\OneToMany(targetEntity: CallerId::class, mappedBy: 'room', orphanRemoval: true, cascade: ['persist'])]
    #[Ignore]
    private $callerIds;
    #[ORM\ManyToOne(targetEntity: Tag::class, inversedBy: 'rooms')]
    #[Ignore]
    private $tag;
    #[ORM\Column(type: 'text', nullable: true)]
    private $hostUrl;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $secondaryName = null;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: CalloutSession::class, orphanRemoval: true)]
    #[Ignore]
    private Collection $calloutSessions;

    #[ORM\ManyToOne(inversedBy: 'creatorOf')]
    #[ORM\JoinColumn(nullable: true)]
    #[Ignore]
    private ?User $creator = null;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Log::class)]
    private Collection $logs;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $allowMaybeOption = true;

    #[ORM\Column(nullable: true)]
    private ?int $maxUser = null;


    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->userAttributes = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
        $this->schedulings = new ArrayCollection();
        $this->waitinglists = new ArrayCollection();
        $this->prototypeUsers = new ArrayCollection();
        $this->favoriteUsers = new ArrayCollection();
        $this->lobbyWaitungUsers = new ArrayCollection();
        $this->roomstatuses = new ArrayCollection();
        $this->callerIds = new ArrayCollection();
        $this->calloutSessions = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    public function normalize(string $propertyName): string
    {
        return 'org_' . $propertyName;
    }

    #[ORM\PreFlush]
    public function preUpdate()
    {
        $timezone = $this->timeZone ? new \DateTimeZone($this->timeZone) : null;
        if ($this->start) {
            $dateStart = new \DateTime($this->start->format('Y-m-d H:i:s'), $timezone);
            $this->startUtc = $dateStart->setTimezone(new \DateTimeZone('utc'));
            $this->startTimestamp = $dateStart->getTimestamp();
        }
        if ($this->enddate) {
            $dateEnd = new \DateTime($this->enddate->format('Y-m-d H:i:s'), $timezone);
            $this->endDateUtc = $dateEnd->setTimezone(new \DateTimeZone('utc'));
            $this->endTimestamp = $dateEnd->getTimestamp();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
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
        return strtolower($this->uid ?? '');
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

    public function getRepeater(): ?Repeat
    {
        return $this->repeater;
    }

    public function setRepeater(?Repeat $repeater): self
    {
        $this->repeater = $repeater;

        return $this;
    }

    public function getRepeaterRemoved(): ?bool
    {
        return $this->repeaterRemoved;
    }

    public function setRepeaterRemoved(?bool $repeaterRemoved): self
    {
        $this->repeaterRemoved = $repeaterRemoved;

        return $this;
    }

    public function getRepeaterProtoype(): ?Repeat
    {
        return $this->repeaterProtoype;
    }

    public function setRepeaterProtoype(Repeat $repeaterProtoype): self
    {
        // set the owning side of the relation if necessary
        if ($repeaterProtoype->getPrototyp() !== $this) {
            $repeaterProtoype->setPrototyp($this);
        }

        $this->repeaterProtoype = $repeaterProtoype;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getPrototypeUsers(): Collection
    {
        return $this->prototypeUsers;
    }

    public function addPrototypeUser(User $prototypeUser): self
    {
        if (!$this->prototypeUsers->contains($prototypeUser)) {
            $this->prototypeUsers[] = $prototypeUser;
        }

        return $this;
    }

    public function removePrototypeUser(User $prototypeUser): self
    {
        $this->prototypeUsers->removeElement($prototypeUser);

        return $this;
    }

    public function getPersistantRoom(): ?bool
    {
        return $this->persistantRoom;
    }

    public function setPersistantRoom(?bool $persistantRoom): self
    {
        $this->persistantRoom = $persistantRoom;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getTotalOpenRooms(): ?bool
    {
        return $this->totalOpenRooms;
    }

    public function setTotalOpenRooms(?bool $totalOpenRooms): self
    {
        $this->totalOpenRooms = $totalOpenRooms;

        return $this;
    }

    public function getTotalOpenRoomsOpenTime(): ?int
    {
        return $this->totalOpenRoomsOpenTime;
    }

    public function setTotalOpenRoomsOpenTime(?int $totalOpenRoomsOpenTime): self
    {
        $this->totalOpenRoomsOpenTime = $totalOpenRoomsOpenTime;

        return $this;
    }

    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    public function setTimeZone(?string $timeZone): self
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    public function getTimeZoneAuto(): ?string
    {
        if ($this->timeZone) {
            return $this->timeZone;
        } else {
            return $this->moderator->getTimeZone();
        }
    }

    public function getStartwithTimeZone(?User $user): ?\DateTimeInterface
    {
        if ($this->timeZone && $user && $user->getTimeZone()) {
            $data = new \DateTime($this->start->format('Y-m-d H:i:s'), new \DateTimeZone($this->timeZone));
            $laTimezone = new \DateTimeZone($user->getTimeZone());
            $data->setTimezone($laTimezone);
            return $data;
        } else {
            return $this->start;
        }
    }

    public function getEndwithTimeZone(?User $user): ?\DateTimeInterface
    {
        if ($this->timeZone && $user && $user->getTimeZone()) {
            $data = new \DateTime($this->enddate->format('Y-m-d H:i:s'), new \DateTimeZone($this->timeZone));
            $laTimezone = new \DateTimeZone($user->getTimeZone());
            $data->setTimezone($laTimezone);
            return $data;
        } else {
            return $this->enddate;
        }
    }

    public function getStartUtc(): ?\DateTimeInterface
    {
        return $this->startUtc ? new \DateTime($this->startUtc->format('Y-m-d H:i:s'), new \DateTimeZone('utc')) : null;
    }

    public function setStartUtc(?\DateTimeInterface $startUtc): self
    {
        $this->startUtc = $startUtc;

        return $this;
    }

    public function getEndDateUtc(): ?\DateTimeInterface
    {
        return $this->endDateUtc ? new \DateTime($this->endDateUtc->format('Y-m-d H:i:s'), new \DateTimeZone('utc')) : null;
    }

    public function setEndDateUtc(?\DateTimeInterface $endDateUtc): self
    {
        $this->endDateUtc = $endDateUtc;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getFavoriteUsers(): Collection
    {
        return $this->favoriteUsers;
    }

    public function addFavoriteUser(User $favoriteUser): self
    {
        if (!$this->favoriteUsers->contains($favoriteUser)) {
            $this->favoriteUsers[] = $favoriteUser;
            $favoriteUser->addFavorite($this);
        }

        return $this;
    }

    public function removeFavoriteUser(User $favoriteUser): self
    {
        if ($this->favoriteUsers->removeElement($favoriteUser)) {
            $favoriteUser->removeFavorite($this);
        }

        return $this;
    }

    public function getLobby(): ?bool
    {
        return $this->lobby;
    }

    public function setLobby(?bool $lobby): self
    {
        $this->lobby = $lobby;

        return $this;
    }

    /**
     * @return Collection|LobbyWaitungUser[]
     */
    public function getLobbyWaitungUsers(): Collection
    {
        return $this->lobbyWaitungUsers;
    }

    public function addLobbyWaitungUser(LobbyWaitungUser $lobbyWaitungUser): self
    {
        if (!$this->lobbyWaitungUsers->contains($lobbyWaitungUser)) {
            $this->lobbyWaitungUsers[] = $lobbyWaitungUser;
            $lobbyWaitungUser->setRoom($this);
        }

        return $this;
    }

    public function removeLobbyWaitungUser(LobbyWaitungUser $lobbyWaitungUser): self
    {
        if ($this->lobbyWaitungUsers->removeElement($lobbyWaitungUser)) {
            // set the owning side to null (unless already changed)
            if ($lobbyWaitungUser->getRoom() === $this) {
                $lobbyWaitungUser->setRoom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Roomstatus[]
     */
    public function getRoomstatuses(): Collection
    {
        return $this->roomstatuses;
    }

    public function addRoomstatus(RoomStatus $roomstatus): self
    {
        if (!$this->roomstatuses->contains($roomstatus)) {
            $this->roomstatuses[] = $roomstatus;
            $roomstatus->setRoom($this);
        }

        return $this;
    }

    public function removeRoomstatus(RoomStatus $roomstatus): self
    {
        if ($this->roomstatuses->removeElement($roomstatus)) {
            // set the owning side to null (unless already changed)
            if ($roomstatus->getRoom() === $this) {
                $roomstatus->setRoom(null);
            }
        }

        return $this;
    }

    public function getCallerRoom(): ?CallerRoom
    {
        return $this->callerRoom;
    }

    public function setCallerRoom(CallerRoom $callerRoom): self
    {
        // set the owning side of the relation if necessary
        if ($callerRoom->getRoom() !== $this) {
            $callerRoom->setRoom($this);
        }

        $this->callerRoom = $callerRoom;

        return $this;
    }

    public function getStartTimestamp(): ?int
    {
        return $this->startTimestamp;
    }

    public function setStartTimestamp(?int $startTimestamp): self
    {
        $this->startTimestamp = $startTimestamp;

        return $this;
    }

    public function getEndTimestamp(): ?int
    {
        return $this->endTimestamp;
    }

    public function setEndTimestamp(?int $endTimestamp): self
    {
        $this->endTimestamp = $endTimestamp;

        return $this;
    }

    /**
     * @return Collection|CallerId[]
     */
    public function getCallerIds(): Collection
    {
        return $this->callerIds;
    }

    public function addCallerId(CallerId $callerId): self
    {
        if (!$this->callerIds->contains($callerId)) {
            $this->callerIds[] = $callerId;
            $callerId->setRoom($this);
        }

        return $this;
    }

    public function removeCallerId(CallerId $callerId): self
    {
        if ($this->callerIds->removeElement($callerId)) {
            // set the owning side to null (unless already changed)
            if ($callerId->getRoom() === $this) {
                $callerId->setRoom(null);
            }
        }

        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getHostUrl(): ?string
    {
        return $this->hostUrl;
    }

    public function setHostUrl(?string $hostUrl): self
    {
        $this->hostUrl = $hostUrl;

        return $this;
    }

    public function getSecondaryName(): ?string
    {
        return $this->secondaryName;
    }

    public function setSecondaryName(?string $secondaryName): self
    {
        $this->secondaryName = $secondaryName;

        return $this;
    }

    /**
     * @return Collection<int, CalloutSession>
     */
    public function getCalloutSessions(): Collection
    {
        return $this->calloutSessions;
    }

    public function addCalloutSession(CalloutSession $calloutSession): self
    {
        if (!$this->calloutSessions->contains($calloutSession)) {
            $this->calloutSessions[] = $calloutSession;
            $calloutSession->setRoom($this);
        }

        return $this;
    }

    public function removeCalloutSession(CalloutSession $calloutSession): self
    {
        if ($this->calloutSessions->removeElement($calloutSession)) {
            // set the owning side to null (unless already changed)
            if ($calloutSession->getRoom() === $this) {
                $calloutSession->setRoom(null);
            }
        }

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return Collection<int, Log>
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setRoom($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getRoom() === $this) {
                $log->setRoom(null);
            }
        }

        return $this;
    }

    public function getAllowMaybeOption(): bool
    {
        return $this->allowMaybeOption;
    }

    public function setAllowMaybeOption(bool $allowMaybeOption): self
    {
        $this->allowMaybeOption = $allowMaybeOption;

        return $this;
    }

    public function getMaxUser(): ?int
    {
        return $this->maxUser;
    }

    public function setMaxUser(?int $maxUser): static
    {
        $this->maxUser = $maxUser;

        return $this;
    }
}
