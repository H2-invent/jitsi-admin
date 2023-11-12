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
    private $id;
    #[ORM\Column(type: 'boolean')]
    private $created;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $RoomCreatedAt;
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $destroyed;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $destroyedAt;
    #[ORM\Column(type: 'datetime')]
    private $createdAt;
    #[ORM\Column(type: 'datetime')]
    private $updatedAt;
    #[ORM\OneToMany(targetEntity: RoomStatusParticipant::class, mappedBy: 'roomStatus', orphanRemoval: true)]
    private $roomStatusParticipants;
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'roomstatuses')]
    #[ORM\JoinColumn(nullable: true)]
    private $room;
    #[ORM\Column(type: 'text')]
    private $jitsiRoomId;
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
    public function getRoomCreatedAt(): ?\DateTimeInterface
    {
        return $this->RoomCreatedAt;
    }
    public function setRoomCreatedAt(?\DateTimeInterface $RoomCreatedAt): self
    {
        $this->RoomCreatedAt = $RoomCreatedAt;

        return $this;
    }
    public function getRoomCreatedAtUTC(): ?\DateTimeInterface
    {
        return new \DateTime($this->RoomCreatedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function getRoomCreatedAtwithTimeZone(?User $user = null): ?\DateTimeInterface
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
                $localTimezone = (new \DateTime())->getTimezone();
            }
        }
        $data->setTimeZone($localTimezone);
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
    public function getDestroyedAt(): ?\DateTimeInterface
    {
        return $this->destroyedAt;
    }
    public function getDestroyedAtwithTimeZone(?User $user = null): ?\DateTimeInterface
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
                $localTimezone = (new \DateTime())->getTimezone();
            }
        }
        $data->setTimeZone($localTimezone);
        return $data;
    }
    public function getDestroyedAtUTC(): ?\DateTimeInterface
    {
        if (!$this->destroyedAt) {
            return null;
        }
        return new \DateTime($this->destroyedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function setDestroyedAt(?\DateTimeInterface $destroyedAt): self
    {
        $this->destroyedAt = $destroyedAt;

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
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    /**
     * @return Collection|RoomStatusParticipant[]
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
    public function getCreatedUtc(): ?\DateTimeInterface
    {
        return new \DateTime($this->RoomCreatedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function getDestroyedUtc(): ?\DateTimeInterface
    {
        if ($this->destroyedAt) {
            return new \DateTime($this->destroyedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
        } else {
            return new \DateTime($this->updatedAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
        }
    }
}
