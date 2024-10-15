<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Service\FormatName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\UserBase as BaseUser;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @Vich\Uploadable()
 */
#[ORM\Table(name: 'fos_user')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseUser
{
    private FormatName $formatName;
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;
    #[Assert\NotBlank(message: 'fos_user.password.blank', groups: ['Registration', 'ResetPassword', 'ChangePassword'])]
    #[Assert\Length(min: 8, minMessage: 'fos_user.password.short', groups: ['Registration', 'Profile', 'ResetPassword', 'ChangePassword'])]
    protected $plainPassword;
    #[ORM\Column(type: 'text')]
    private $email;
    #[ORM\Column(type: 'text', nullable: true)]
    private $keycloakId;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;
    #[ORM\Column(type: 'text', nullable: true)]
    private $username;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $lastLogin;
    #[ORM\Column(type: 'text', nullable: true)]
    private $firstName;
    #[ORM\Column(type: 'text', nullable: true)]
    private $lastName;
    #[ORM\Column(type: 'text', nullable: true)]
    private $registerId;
    #[ORM\ManyToMany(targetEntity: Rooms::class, mappedBy: 'user')]
    private $rooms;
    #[ORM\ManyToMany(targetEntity: Server::class, mappedBy: 'user')]
    private $servers;
    #[ORM\OneToMany(targetEntity: Rooms::class, mappedBy: 'moderator')]
    private $roomModerator;
    #[ORM\OneToMany(targetEntity: Server::class, mappedBy: 'administrator')]
    private $serverAdmins;
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'addressbookInverse')]
    private $addressbook;
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'addressbook')]
    private $addressbookInverse;
    #[ORM\OneToMany(targetEntity: RoomsUser::class, mappedBy: 'user')]
    private $roomsAttributes;
    #[ORM\OneToMany(targetEntity: Subscriber::class, mappedBy: 'user')]
    private $subscribers;
    #[ORM\Column(type: 'array', nullable: true, name: 'keycloakGroup')]
    private $groups = [];
    #[ORM\OneToMany(targetEntity: SchedulingTimeUser::class, mappedBy: 'user')]
    private $schedulingTimeUsers;
    #[ORM\Column(type: 'text', nullable: true)]
    private $uid;
    #[ORM\OneToMany(targetEntity: Waitinglist::class, mappedBy: 'user', cascade: ['remove'])]
    private $waitinglists;
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private $notifications;
    #[ORM\ManyToMany(targetEntity: Repeat::class, mappedBy: 'participants')]
    private $repeaterUsers;
    #[ORM\ManyToMany(targetEntity: Rooms::class, mappedBy: 'prototypeUsers')]
    private $protoypeRooms;
    #[ORM\Column(type: 'text', nullable: true)]
    private $ownRoomUid;
    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'OwnRoomUSer')]
    private $myOwnRoomServer;
    #[ORM\OneToMany(targetEntity: AddressGroup::class, mappedBy: 'leader', cascade: ['remove'])]
    private $AddressGroupLeader;
    #[ORM\ManyToMany(targetEntity: AddressGroup::class, mappedBy: 'member')]
    private $AddressGroupMember;
    #[ORM\OneToOne(targetEntity: LdapUserProperties::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $ldapUserProperties;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $timeZone;
    #[ORM\Column(type: 'array', nullable: true)]
    private $spezialProperties = [];
    #[ORM\ManyToMany(targetEntity: Rooms::class, inversedBy: 'favoriteUsers')]
    private $favorites;
    #[ORM\OneToMany(targetEntity: LobbyWaitungUser::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $lobbyWaitungUsers;
    #[ORM\Column(type: 'text', nullable: true)]
    private $indexer;
    #[ORM\Column(type: 'text', nullable: true)]
    private $secondEmail;
    /**
     * @var Documents
     */
    #[ORM\OneToOne(targetEntity: Documents::class, cascade: ['persist', 'remove'])]
    private $profilePicture;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;
    #[ORM\OneToMany(targetEntity: CallerId::class, mappedBy: 'user', cascade: ['remove'])]
    private $callerIds;

    #[ORM\Column(nullable: true)]
    private ?int $onlineStatus = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CalloutSession::class, orphanRemoval: true)]
    private Collection $calloutSessions;

    #[ORM\Column(nullable: true)]
    private ?bool $acceptTermsAndConditions = null;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Rooms::class)]
    private Collection $creatorOf;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Log::class)]
    private Collection $logs;

    #[ORM\OneToMany(mappedBy: 'deputy', targetEntity: Deputy::class, orphanRemoval: true)]
    private Collection $deputiesElement;

    #[ORM\OneToMany(mappedBy: 'manager', targetEntity: Deputy::class, orphanRemoval: true)]
    private Collection $managerElement;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'isAdressbookFavoriteFrom')]
    #[ORM\JoinTable(name: 'addressbook_favorites')]
    private Collection $AdressbookFavorites;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'AdressbookFavorites')]
    #[ORM\JoinTable(name: 'addressbook_favorites')]
    private Collection $isAdressbookFavoriteFrom;

    #[ORM\OneToMany(mappedBy: 'createdFrom', targetEntity: SchedulingTime::class)]
    private Collection $schedulingTimesCreated;

    #[ORM\Column(nullable: true)]
    private ?bool $isSipVideoUser = null;

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
        $this->notifications = new ArrayCollection();
        $this->repeaterUsers = new ArrayCollection();
        $this->protoypeRooms = new ArrayCollection();
        $this->AddressGroupLeader = new ArrayCollection();
        $this->AddressGroupMember = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->lobbyWaitungUsers = new ArrayCollection();
        $this->callerIds = new ArrayCollection();
        $this->calloutSessions = new ArrayCollection();
        $this->creatorOf = new ArrayCollection();
        $this->logs = new ArrayCollection();
        $this->deputiesElement = new ArrayCollection();
        $this->managerElement = new ArrayCollection();
        $this->AdressbookFavorites = new ArrayCollection();
        $this->isAdressbookFavoriteFrom = new ArrayCollection();
        $this->schedulingTimesCreated = new ArrayCollection();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = rtrim($email);

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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = rtrim($username);

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

    /**
     * @return Collection|Notification[]
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Repeat[]
     */
    public function getRepeaterUsers(): Collection
    {
        return $this->repeaterUsers;
    }

    public function addRepeaterUser(Repeat $repeaterUser): self
    {
        if (!$this->repeaterUsers->contains($repeaterUser)) {
            $this->repeaterUsers[] = $repeaterUser;
            $repeaterUser->addParticipant($this);
        }

        return $this;
    }

    public function removeRepeaterUser(Repeat $repeaterUser): self
    {
        if ($this->repeaterUsers->removeElement($repeaterUser)) {
            $repeaterUser->removeParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection|Rooms[]
     */
    public function getProtoypeRooms(): Collection
    {
        return $this->protoypeRooms;
    }

    public function addProtoypeRoom(Rooms $protoypeRoom): self
    {
        if (!$this->protoypeRooms->contains($protoypeRoom)) {
            $this->protoypeRooms[] = $protoypeRoom;
            $protoypeRoom->addPrototypeUser($this);
        }

        return $this;
    }

    public function removeProtoypeRoom(Rooms $protoypeRoom): self
    {
        if ($this->protoypeRooms->removeElement($protoypeRoom)) {
            $protoypeRoom->removePrototypeUser($this);
        }

        return $this;
    }

    public function getOwnRoomUid(): ?string
    {
        return $this->ownRoomUid;
    }

    public function setOwnRoomUid(?string $ownRoomUid): self
    {
        $this->ownRoomUid = $ownRoomUid;

        return $this;
    }

    public function getMyOwnRoomServer(): ?Server
    {
        return $this->myOwnRoomServer;
    }

    public function setMyOwnRoomServer(?Server $myOwnRoomServer): self
    {
        $this->myOwnRoomServer = $myOwnRoomServer;

        return $this;
    }

    /**
     * @return Collection|AddressGroup[]
     */
    public function getAddressGroupLeader(): Collection
    {
        return $this->AddressGroupLeader;
    }

    public function addAddressGroupLeader(AddressGroup $addressGroupLeader): self
    {
        if (!$this->AddressGroupLeader->contains($addressGroupLeader)) {
            $this->AddressGroupLeader[] = $addressGroupLeader;
            $addressGroupLeader->setLeader($this);
        }

        return $this;
    }

    public function removeAddressGroupLeader(AddressGroup $addressGroupLeader): self
    {
        if ($this->AddressGroupLeader->removeElement($addressGroupLeader)) {
            // set the owning side to null (unless already changed)
            if ($addressGroupLeader->getLeader() === $this) {
                $addressGroupLeader->setLeader(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AddressGroup[]
     */
    public function getAddressGroupMember(): Collection
    {
        return $this->AddressGroupMember;
    }

    public function addAddressGroupMember(AddressGroup $addressGroupMember): self
    {
        if (!$this->AddressGroupMember->contains($addressGroupMember)) {
            $this->AddressGroupMember[] = $addressGroupMember;
            $addressGroupMember->addMember($this);
        }

        return $this;
    }

    public function removeAddressGroupMember(AddressGroup $addressGroupMember): self
    {
        if ($this->AddressGroupMember->removeElement($addressGroupMember)) {
            $addressGroupMember->removeMember($this);
        }

        return $this;
    }

    public function getLdapUserProperties(): ?LdapUserProperties
    {
        return $this->ldapUserProperties;
    }

    public function setLdapUserProperties(LdapUserProperties $ldapUserProperties): self
    {
        // set the owning side of the relation if necessary
        if ($ldapUserProperties->getUser() !== $this) {
            $ldapUserProperties->setUser($this);
        }

        $this->ldapUserProperties = $ldapUserProperties;

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

    public function getSpezialProperties(): ?array
    {
        return $this->spezialProperties;
    }

    public function setSpezialProperties(?array $spezialProperties): self
    {
        $this->spezialProperties = $spezialProperties;

        return $this;
    }

    public function getFormatedName($string)
    {
        $this->formatName = new FormatName();
        return $this->formatName->formatName($string, $this);
    }

    public function getUserIdentifier(): string
    {
        return $this->username?:'';
    }

    /**
     * @return Collection|Rooms[]
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Rooms $favorite): self
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites[] = $favorite;
        }

        return $this;
    }

    public function removeFavorite(Rooms $favorite): self
    {
        $this->favorites->removeElement($favorite);

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
            $lobbyWaitungUser->setUser($this);
        }

        return $this;
    }

    public function removeLobbyWaitungUser(LobbyWaitungUser $lobbyWaitungUser): self
    {
        if ($this->lobbyWaitungUsers->removeElement($lobbyWaitungUser)) {
            // set the owning side to null (unless already changed)
            if ($lobbyWaitungUser->getUser() === $this) {
                $lobbyWaitungUser->setUser(null);
            }
        }

        return $this;
    }

    public function getPermissionForRoom(Rooms $rooms): RoomsUser
    {

        foreach ($this->roomsAttributes as $data) {
            if ($data->getRoom()->getId() == $rooms->getId()) {
                return $data;
            }
        }

        return new RoomsUser();
    }

    public function getIndexer(): ?string
    {
        return $this->indexer;
    }

    public function setIndexer(?string $indexer): self
    {
        $this->indexer = $indexer;

        return $this;
    }

    public function getSecondEmail(): ?string
    {
        return $this->secondEmail;
    }

    public function setSecondEmail(?string $secondEmail): self
    {
        $this->secondEmail = $secondEmail;

        return $this;
    }

    public function getProfilePicture(): ?Documents
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?Documents $profilePicture): self
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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
            $callerId->setUser($this);
        }

        return $this;
    }

    public function removeCallerId(CallerId $callerId): self
    {
        if ($this->callerIds->removeElement($callerId)) {
            // set the owning side to null (unless already changed)
            if ($callerId->getUser() === $this) {
                $callerId->setUser(null);
            }
        }

        return $this;
    }

    public function getCategories()
    {
        $res = [];

        if ($this->ldapUserProperties) {
            $res[] = $this->ldapUserProperties->getLdapNumber();
        }
        return $res;
    }

    public function getOnlineStatus(): ?int
    {
        return $this->onlineStatus;
    }

    public function setOnlineStatus(?int $onlineStatus): self
    {
        $this->onlineStatus = $onlineStatus;

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
            $calloutSession->setUser($this);
        }

        return $this;
    }

    public function removeCalloutSession(CalloutSession $calloutSession): self
    {
        if ($this->calloutSessions->removeElement($calloutSession)) {
            // set the owning side to null (unless already changed)
            if ($calloutSession->getUser() === $this) {
                $calloutSession->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getDeputy(): Collection
    {
        $deputy = [];
        foreach ($this->getManagerElement() as $data) {
            $deputy[] = $data->getDeputy();
        }
        return new ArrayCollection($deputy);
    }

    public function addDeputy(self $deputy): self
    {
        if (!$this->deputy->contains($deputy)) {
            $this->deputy[] = $deputy;
        }

        return $this;
    }

    public function removeDeputy(self $deputy): self
    {
        $this->deputy->removeElement($deputy);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getManagers(): Collection
    {
        $managers = [];
        foreach ($this->getDeputiesElement() as $data) {
            $managers[] = $data->getManager();
        }
        return new ArrayCollection($managers);
    }

    public function addManager(self $manager): self
    {
        if (!$this->managers->contains($manager)) {
            $this->managers[] = $manager;
            $manager->addDeputy($this);
        }

        return $this;
    }

    public function removeManager(self $manager): self
    {
        if ($this->managers->removeElement($manager)) {
            $manager->removeDeputy($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Rooms>
     */
    public function getCreatorOf(): Collection
    {
        return $this->creatorOf;
    }

    public function addCreatorOf(Rooms $creatorOf): self
    {
        if (!$this->creatorOf->contains($creatorOf)) {
            $this->creatorOf[] = $creatorOf;
            $creatorOf->setCreator($this);
        }

        return $this;
    }

    public function removeCreatorOf(Rooms $creatorOf): self
    {
        if ($this->creatorOf->removeElement($creatorOf)) {
            // set the owning side to null (unless already changed)
            if ($creatorOf->getCreator() === $this) {
                $creatorOf->setCreator(null);
            }
        }

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
            $log->setUser($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->removeElement($log)) {
            // set the owning side to null (unless already changed)
            if ($log->getUser() === $this) {
                $log->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Deputy>
     */
    public function getDeputiesElement(): Collection
    {
        return $this->deputiesElement;
    }

    public function addDeputiesElement(Deputy $deputiesElement): self
    {
        if (!$this->deputiesElement->contains($deputiesElement)) {
            $this->deputiesElement->add($deputiesElement);
            $deputiesElement->setDeputy($this);
        }

        return $this;
    }

    public function removeDeputiesElement(Deputy $deputiesElement): self
    {
        if ($this->deputiesElement->removeElement($deputiesElement)) {
            // set the owning side to null (unless already changed)
            if ($deputiesElement->getDeputy() === $this) {
                $deputiesElement->setDeputy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Deputy>
     */
    public function getManagerElement(): Collection
    {
        return $this->managerElement;
    }

    public function addManagerElement(Deputy $managerElement): self
    {
        if (!$this->managerElement->contains($managerElement)) {
            $this->managerElement->add($managerElement);
            $managerElement->setManager($this);
        }

        return $this;
    }

    public function removeManagerElement(Deputy $managerElement): self
    {
        if ($this->managerElement->removeElement($managerElement)) {
            // set the owning side to null (unless already changed)
            if ($managerElement->getManager() === $this) {
                $managerElement->setManager(null);
            }
        }

        return $this;
    }

    public function isAcceptTermsAndConditions(): ?bool
    {
        return $this->acceptTermsAndConditions;
    }

    public function setAcceptTermsAndConditions(?bool $acceptTermsAndConditions): self
    {
        $this->acceptTermsAndConditions = $acceptTermsAndConditions;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getAdressbookFavorites(): Collection
    {
        return $this->AdressbookFavorites;
    }

    public function addAdressbookFavorite(self $adressbookFavorite): self
    {
        if (!$this->AdressbookFavorites->contains($adressbookFavorite)) {
            $this->AdressbookFavorites->add($adressbookFavorite);
        }

        return $this;
    }

    public function removeAdressbookFavorite(self $adressbookFavorite): self
    {
        $this->AdressbookFavorites->removeElement($adressbookFavorite);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getIsAdressbookFavoriteFrom(): Collection
    {
        return $this->isAdressbookFavoriteFrom;
    }

    public function addIsAdressbookFavoriteFrom(self $isAdressbookFavoriteFrom): self
    {
        if (!$this->isAdressbookFavoriteFrom->contains($isAdressbookFavoriteFrom)) {
            $this->isAdressbookFavoriteFrom->add($isAdressbookFavoriteFrom);
            $isAdressbookFavoriteFrom->addAdressbookFavorite($this);
        }

        return $this;
    }

    public function removeIsAdressbookFavoriteFrom(self $isAdressbookFavoriteFrom): self
    {
        if ($this->isAdressbookFavoriteFrom->removeElement($isAdressbookFavoriteFrom)) {
            $isAdressbookFavoriteFrom->removeAdressbookFavorite($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, SchedulingTime>
     */
    public function getSchedulingTimesCreated(): Collection
    {
        return $this->schedulingTimesCreated;
    }

    public function addSchedulingTimesCreated(SchedulingTime $schedulingTimesCreated): self
    {
        if (!$this->schedulingTimesCreated->contains($schedulingTimesCreated)) {
            $this->schedulingTimesCreated->add($schedulingTimesCreated);
            $schedulingTimesCreated->setCreatedFrom($this);
        }

        return $this;
    }

    public function removeSchedulingTimesCreated(SchedulingTime $schedulingTimesCreated): self
    {
        if ($this->schedulingTimesCreated->removeElement($schedulingTimesCreated)) {
            // set the owning side to null (unless already changed)
            if ($schedulingTimesCreated->getCreatedFrom() === $this) {
                $schedulingTimesCreated->setCreatedFrom(null);
            }
        }

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

    public function getIsSipVideoUser(): ?bool
    {
        return $this->isSipVideoUser;
    }


}
