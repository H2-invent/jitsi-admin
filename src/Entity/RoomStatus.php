<?php

namespace App\Entity;

use App\Repository\RoomStatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomStatusRepository::class)]
class RoomStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    private bool $created;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $RoomCreatedAt = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $destroyed = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $destroyedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, RoomStatusParticipant>
     */
    #[ORM\OneToMany(targetEntity: RoomStatusParticipant::class, mappedBy: 'roomStatus', orphanRemoval: true)]
    private Collection $roomStatusParticipants;

    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'roomstatuses')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Rooms $room = null;

    #[ORM\Column(type: 'text')]
    private string $jitsiRoomId;

    public function __construct()
    {
        $this->roomStatusParticipants = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCreated(): ?bool
    {
        return $this->created;
    }
    public function setCreated(bool $created): self
    {
        $this->created = $created;

        return $this;
    }
    public function getRoomCreatedAt(): ?\DateTimeImmutable
    {
        return $this->RoomCreatedAt;
    }
    public function setRoomCreatedAt(?\DateTimeImmutable $RoomCreatedAt): self
    {
        $this->RoomCreatedAt = $RoomCreatedAt;

        return $this;
    }
    public function getRoomCreatedAtUTC(): ?\DateTimeImmutable
    {
        return new \DateTimeImmutable($this->RoomCreatedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function getRoomCreatedAtwithTimeZone(?User $user = null): ?\DateTimeImmutable
    {
        $data = $this->getCreatedUtc();
        if (!$data) {
            return null;
        }
        if ($user && $user->getTimeZone()) {
            $localTimezone = new \DateTimeZone($user->getTimeZone());
        } else {
            if ($this->room && $this->room->getTimeZone()) {
                $localTimezone = new \DateTimeZone($this->room->getTimeZone());
            } else {
                $localTimezone = (new \DateTimeImmutable())->getTimezone();
            }
        }
        $data = $data->setTimeZone($localTimezone);
        return $data;
    }
    public function getDestroyed(): ?bool
    {
        return $this->destroyed;
    }
    public function setDestroyed(?bool $destroyed): self
    {
        $this->destroyed = $destroyed;

        return $this;
    }
    public function getDestroyedAt(): ?\DateTimeImmutable
    {
        return $this->destroyedAt;
    }
    public function getDestroyedAtwithTimeZone(?User $user = null): ?\DateTimeImmutable
    {
        $data = $this->getDestroyedAtUTC();
        if (!$data) {
            return null;
        }
        if ($user && $user->getTimeZone()) {
            $localTimezone = new \DateTimeZone($user->getTimeZone());
        } else {
            if ($this->room && $this->room->getTimeZone()) {
                $localTimezone = new \DateTimeZone($this->room->getTimeZone());
            } else {
                $localTimezone = (new \DateTimeImmutable())->getTimezone();
            }
        }
        $data = $data->setTimeZone($localTimezone);
        return $data;
    }
    public function getDestroyedAtUTC(): ?\DateTimeImmutable
    {
        if (!$this->destroyedAt) {
            return null;
        }
        return new \DateTimeImmutable($this->destroyedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function setDestroyedAt(?\DateTimeImmutable $destroyedAt): self
    {
        $this->destroyedAt = $destroyedAt;

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
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    /**
     * @return Collection<int, RoomStatusParticipant>
     */
    public function getRoomStatusParticipants(): Collection
    {
        return $this->roomStatusParticipants;
    }
    public function addRoomStatusParticipant(RoomStatusParticipant $roomStatusParticipant): self
    {
        if (!$this->roomStatusParticipants->contains($roomStatusParticipant)) {
            $this->roomStatusParticipants[] = $roomStatusParticipant;
            $roomStatusParticipant->setRoomStatus($this);
        }

        return $this;
    }
    public function removeRoomStatusParticipant(RoomStatusParticipant $roomStatusParticipant): self
    {
        if ($this->roomStatusParticipants->removeElement($roomStatusParticipant)) {
            // set the owning side to null (unless already changed)
            if ($roomStatusParticipant->getRoomStatus() === $this) {
                $roomStatusParticipant->setRoomStatus(null);
            }
        }

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
    public function getJitsiRoomId(): ?string
    {
        return $this->jitsiRoomId;
    }
    public function setJitsiRoomId(string $jitsiRoomId): self
    {
        $this->jitsiRoomId = $jitsiRoomId;

        return $this;
    }
    public function getCreatedUtc(): ?\DateTimeImmutable
    {
        return new \DateTimeImmutable($this->RoomCreatedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function getDestroyedUtc(): ?\DateTimeImmutable
    {
        if ($this->destroyedAt) {
            return new \DateTimeImmutable($this->destroyedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
        } else {
            return new \DateTimeImmutable($this->updatedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
        }
    }
}
