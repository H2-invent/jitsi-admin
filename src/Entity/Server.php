<?php

namespace App\Entity;

use App\Repository\ServerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
class Server
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $url;
    #[ORM\Column(type: 'text', nullable: true)]
    private $appId;
    #[ORM\Column(type: 'text', nullable: true)]
    private $appSecret;
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'servers')]
    private $user;
    #[ORM\OneToMany(targetEntity: Rooms::class, mappedBy: 'server')]
    private $rooms;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'serverAdmins')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private $administrator;
    #[ORM\Column(type: 'text', nullable: true)]
    private $logoUrl;
    #[ORM\Column(type: 'text', nullable: true)]
    private $smtpHost;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $smtpPort;
    #[ORM\Column(type: 'text', nullable: true)]
    private $smtpPassword;
    #[ORM\Column(type: 'text', nullable: true)]
    private $smtpUsername;
    #[ORM\Column(type: 'text', nullable: true)]
    private $smtpEncryption;
    #[ORM\Column(type: 'text', nullable: true)]
    private $smtpEmail;
    #[ORM\Column(type: 'text', nullable: true)]
    private $smtpSenderName;
    #[ORM\Column(type: 'text')]
    private $slug;
    #[ORM\Column(type: 'text', nullable: true)]
    private $privacyPolicy;
    #[ORM\Column(type: 'text', nullable: true)]
    private $licenseKey;
    #[ORM\Column(type: 'text', nullable: true)]
    private $apiKey;
    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    private $staticBackgroundColor;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $showStaticBackgroundColor;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $featureEnableByJWT = false;
    #[ORM\Column(type: 'text', nullable: true)]
    private $serverEmailHeader;
    #[ORM\Column(type: 'text', nullable: true)]
    private $serverEmailBody;
    #[ORM\OneToMany(targetEntity: KeycloakGroupsToServers::class, mappedBy: 'server', cascade: ['persist'])]
    private $keycloakGroups;
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'myOwnRoomServer')]
    private $OwnRoomUSer;
    #[ORM\Column(type: 'integer')]
    private $jwtModeratorPosition;
    #[ORM\Column(type: 'text')]
    private $serverName;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $corsHeader;
    #[ORM\OneToMany(targetEntity: Star::class, mappedBy: 'server', orphanRemoval: true)]
    private $stars;
    /**
     * @var Documents
     */
    #[ORM\OneToOne(targetEntity: Documents::class, cascade: ['persist', 'remove'])]
    private $serverBackgroundImage;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $jigasiApiUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $jigasiNumberUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $jigasiProsodyDomain = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $starUrl = null;

    #[ORM\Column(nullable: true)]
    private ?int $starServerId = null;

    #[ORM\Column(nullable: true)]
    private ?bool $disallowFirefox = null;

    #[ORM\Column(nullable: true)]
    private ?bool $enforceE2e = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $allowIp = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'servers')]
    private Collection $tag;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $dynamicBrandingUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jitsiEventSyncUrl = null;

    #[ORM\Column(nullable: true)]
    private ?bool $disableFilmstripe = null;

    #[ORM\Column(nullable: true)]
    private ?bool $disableEtherpad = null;

    #[ORM\Column(nullable: true)]
    private ?bool $disableWhiteboard = null;

    #[ORM\Column(nullable: true)]
    private ?bool $disableChat = null;

    #[ORM\Column(nullable: true)]
    private ?bool $prefixRoomUidWithHash = null;
    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->rooms = new ArrayCollection();
        $this->keycloakGroups = new ArrayCollection();
        $this->OwnRoomUSer = new ArrayCollection();
        $this->stars = new ArrayCollection();
        $this->tag = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUrl(): ?string
    {
        return $this->url;
    }
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
    public function getAppId(): ?string
    {
        return $this->appId;
    }
    public function setAppId(?string $appId): self
    {
        $this->appId = $appId;

        return $this;
    }
    public function getAppSecret(): ?string
    {
        return $this->appSecret;
    }
    public function setAppSecret(?string $appSecret): self
    {
        $this->appSecret = $appSecret;

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
            $room->setServer($this);
        }

        return $this;
    }
    public function removeRoom(Rooms $room): self
    {
        if ($this->rooms->removeElement($room)) {
            // set the owning side to null (unless already changed)
            if ($room->getServer() === $this) {
                $room->setServer(null);
            }
        }

        return $this;
    }
    public function getAdministrator(): ?User
    {
        return $this->administrator;
    }
    public function setAdministrator(?User $administrator): self
    {
        $this->administrator = $administrator;

        return $this;
    }
    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }
    public function setLogoUrl(?string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;

        return $this;
    }
    public function getSmtpHost(): ?string
    {
        return $this->smtpHost;
    }
    public function setSmtpHost(?string $smtpHost): self
    {
        $this->smtpHost = $smtpHost;

        return $this;
    }
    public function getSmtpPort(): ?int
    {
        return $this->smtpPort;
    }
    public function setSmtpPort(?int $smtpPort): self
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }
    public function getSmtpPassword(): ?string
    {
        return $this->smtpPassword;
    }
    public function setSmtpPassword(?string $smtpPassword): self
    {
        $this->smtpPassword = $smtpPassword;

        return $this;
    }
    public function getSmtpUsername(): ?string
    {
        return $this->smtpUsername;
    }
    public function setSmtpUsername(?string $smtpUsername): self
    {
        $this->smtpUsername = $smtpUsername;

        return $this;
    }
    public function getSmtpEncryption(): ?string
    {
        return $this->smtpEncryption;
    }
    public function setSmtpEncryption(?string $smtpEncryption): self
    {
        $this->smtpEncryption = $smtpEncryption;

        return $this;
    }
    public function getSmtpEmail(): ?string
    {
        return $this->smtpEmail;
    }
    public function setSmtpEmail(?string $smtpEmail): self
    {
        $this->smtpEmail = $smtpEmail;

        return $this;
    }
    public function getSmtpSenderName(): ?string
    {
        return $this->smtpSenderName;
    }
    public function setSmtpSenderName(?string $smtpSenderName): self
    {
        $this->smtpSenderName = $smtpSenderName;

        return $this;
    }
    public function getSlug(): ?string
    {
        return $this->slug;
    }
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
    public function getSlugMd5(): ?string
    {
        if ($this->isPrefixRoomUidWithHash()){
            return md5($this->id . $this->slug).'/';
        }else{
            return '';
        }

    }

    public function getPrivacyPolicy(): ?string
    {
        return $this->privacyPolicy;
    }
    public function setPrivacyPolicy(?string $privacyPolicy): self
    {
        $this->privacyPolicy = $privacyPolicy;

        return $this;
    }
    public function getLicenseKey(): ?string
    {
        return $this->licenseKey;
    }
    public function setLicenseKey(?string $licenseKey): self
    {
        $this->licenseKey = $licenseKey;

        return $this;
    }
    public function getStaticBackgroundColor(): ?string
    {
        return $this->staticBackgroundColor;
    }
    public function setStaticBackgroundColor(?string $staticBackgroundColor): self
    {
        $this->staticBackgroundColor = $staticBackgroundColor;

        return $this;
    }
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }
    public function setApiKey(?string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }
    public function getShowStaticBackgroundColor(): ?bool
    {
        return $this->showStaticBackgroundColor;
    }
    public function setShowStaticBackgroundColor(?bool $showStaticBackgroundColor): self
    {
        $this->showStaticBackgroundColor = $showStaticBackgroundColor;

        return $this;
    }
    public function getFeatureEnableByJWT(): ?bool
    {
        return $this->featureEnableByJWT;
    }
    public function setFeatureEnableByJWT(?bool $featureEnableByJWT): self
    {
        $this->featureEnableByJWT = $featureEnableByJWT;

        return $this;
    }
    public function getServerEmailHeader(): ?string
    {
        return $this->serverEmailHeader;
    }
    public function setServerEmailHeader(?string $serverEmailHeader): self
    {
        $this->serverEmailHeader = $serverEmailHeader;

        return $this;
    }
    public function getServerEmailBody(): ?string
    {
        return $this->serverEmailBody;
    }
    public function setServerEmailBody(?string $serverEmailBody): self
    {
        $this->serverEmailBody = $serverEmailBody;

        return $this;
    }
    /**
     * @return Collection|KeycloakGroupsToServers[]
     */
    public function getKeycloakGroups(): Collection
    {
        return $this->keycloakGroups;
    }
    public function addKeycloakGroup(KeycloakGroupsToServers $keycloakGroup): self
    {
        if (!$this->keycloakGroups->contains($keycloakGroup)) {
            $this->keycloakGroups[] = $keycloakGroup;
            $keycloakGroup->setServer($this);
        }

        return $this;
    }
    public function removeKeycloakGroup(KeycloakGroupsToServers $keycloakGroup): self
    {
        if ($this->keycloakGroups->removeElement($keycloakGroup)) {
            // set the owning side to null (unless already changed)
            if ($keycloakGroup->getServer() === $this) {
                $keycloakGroup->setServer(null);
            }
        }

        return $this;
    }
    /**
     * @return Collection|User[]
     */
    public function getOwnRoomUSer(): Collection
    {
        return $this->OwnRoomUSer;
    }
    public function addOwnRoomUSer(User $ownRoomUSer): self
    {
        if (!$this->OwnRoomUSer->contains($ownRoomUSer)) {
            $this->OwnRoomUSer[] = $ownRoomUSer;
            $ownRoomUSer->setMyOwnRoomServer($this);
        }

        return $this;
    }
    public function removeOwnRoomUSer(User $ownRoomUSer): self
    {
        if ($this->OwnRoomUSer->removeElement($ownRoomUSer)) {
            // set the owning side to null (unless already changed)
            if ($ownRoomUSer->getMyOwnRoomServer() === $this) {
                $ownRoomUSer->setMyOwnRoomServer(null);
            }
        }

        return $this;
    }
    public function getJwtModeratorPosition(): ?int
    {
        return $this->jwtModeratorPosition;
    }
    public function setJwtModeratorPosition(int $jwtModeratorPosition): self
    {
        $this->jwtModeratorPosition = $jwtModeratorPosition;

        return $this;
    }
    public function getServerName(): ?string
    {
        return $this->serverName;
    }
    public function setServerName(?string $serverName): self
    {
        $this->serverName = $serverName;

        return $this;
    }
    public function getCorsHeader(): ?bool
    {
        return $this->corsHeader;
    }
    public function setCorsHeader(?bool $corsHeader): self
    {
        $this->corsHeader = $corsHeader;

        return $this;
    }
    /**
     * @return Collection<int, Star>
     */
    public function getStars(): Collection
    {
        return $this->stars;
    }
    public function addStar(Star $star): self
    {
        if (!$this->stars->contains($star)) {
            $this->stars[] = $star;
            $star->setServer($this);
        }

        return $this;
    }
    public function removeStar(Star $star): self
    {
        if ($this->stars->removeElement($star)) {
            // set the owning side to null (unless already changed)
            if ($star->getServer() === $this) {
                $star->setServer(null);
            }
        }

        return $this;
    }
    public function getServerBackgroundImage(): ?Documents
    {
        return $this->serverBackgroundImage;
    }
    public function setServerBackgroundImage(?Documents $serverBackgroundImage): self
    {
        $this->serverBackgroundImage = $serverBackgroundImage;

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

    public function getJigasiApiUrl(): ?string
    {
        return $this->jigasiApiUrl;
    }

    public function setJigasiApiUrl(?string $jigasiApiUrl): self
    {
        $this->jigasiApiUrl = $jigasiApiUrl;

        return $this;
    }

    public function getJigasiNumberUrl(): ?string
    {
        return $this->jigasiNumberUrl;
    }

    public function setJigasiNumberUrl(?string $jigasiNumberUrl): self
    {
        $this->jigasiNumberUrl = $jigasiNumberUrl;

        return $this;
    }

    public function getJigasiProsodyDomain(): ?string
    {
        return $this->jigasiProsodyDomain;
    }

    public function setJigasiProsodyDomain(?string $jigasiProsodyDomain): self
    {
        $this->jigasiProsodyDomain = $jigasiProsodyDomain;

        return $this;
    }

    public function getStarUrl(): ?string
    {
        return $this->starUrl;
    }

    public function setStartUrl(?string $starUrl): self
    {
        $this->starUrl = $starUrl;

        return $this;
    }

    public function getStarServerId(): ?int
    {
        return $this->starServerId;
    }

    public function setStarServerId(?int $starServerId): self
    {
        $this->starServerId = $starServerId;

        return $this;
    }

    public function isDisallowFirefox(): ?bool
    {
        return $this->disallowFirefox;
    }

    public function setDisallowFirefox(?bool $disallowFirefox): self
    {
        $this->disallowFirefox = $disallowFirefox;

        return $this;
    }

    public function isEnforceE2e(): ?bool
    {
        return $this->enforceE2e;
    }

    public function setEnforceE2e(?bool $enforceE2e): static
    {
        $this->enforceE2e = $enforceE2e;

        return $this;
    }

    public function getAllowIp(): ?string
    {
        return $this->allowIp;
    }

    public function setAllowIp(?string $allowIp): static
    {
        $this->allowIp = $allowIp;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTag(): Collection
    {
        $data = $this->tag->toArray();
        usort($data, function (Tag $a,Tag $b) {
            return $a->getPriority()> $b->getPriority();
        });
        $res = [];
        foreach ($data as $datum){
            if (!$datum->getDisabled()){
                $res[]=$datum;
            }
        }
        return new ArrayCollection($res);
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tag->contains($tag)) {
            $this->tag->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tag->removeElement($tag);

        return $this;
    }

    public function getDynamicBrandingUrl(): ?string
    {
        return $this->dynamicBrandingUrl;
    }

    public function setDynamicBrandingUrl(?string $dynamicBrandingUrl): static
    {
        $this->dynamicBrandingUrl = $dynamicBrandingUrl;

        return $this;
    }

    public function getJitsiEventSyncUrl(): ?string
    {
        return $this->jitsiEventSyncUrl;
    }

    public function setJitsiEventSyncUrl(?string $jitsiEventSyncUrl): static
    {
        $this->jitsiEventSyncUrl = $jitsiEventSyncUrl;

        return $this;
    }

    public function isDisableFilmstripe(): ?bool
    {
        return $this->disableFilmstripe;
    }

    public function setDisableFilmstripe(?bool $disableFilmstripe): static
    {
        $this->disableFilmstripe = $disableFilmstripe;

        return $this;
    }

    public function isDisableEtherpad(): ?bool
    {
        return $this->disableEtherpad;
    }

    public function setDisableEtherpad(?bool $disableEtherpad): static
    {
        $this->disableEtherpad = $disableEtherpad;

        return $this;
    }

    public function isDisableWhiteboard(): ?bool
    {
        return $this->disableWhiteboard;
    }

    public function setDisableWhiteboard(?bool $disableWhiteboard): static
    {
        $this->disableWhiteboard = $disableWhiteboard;

        return $this;
    }

    public function isDisableChat(): ?bool
    {
        return $this->disableChat;
    }

    public function setDisableChat(bool $disableChat): static
    {
        $this->disableChat = $disableChat;

        return $this;
    }

    public function isPrefixRoomUidWithHash(): ?bool
    {
        return $this->prefixRoomUidWithHash;
    }

    public function setPrefixRoomUidWithHash(?bool $prefixRoomUidWithHash): static
    {
        $this->prefixRoomUidWithHash = $prefixRoomUidWithHash;

        return $this;
    }
}
