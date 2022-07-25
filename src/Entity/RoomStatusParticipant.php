<?php

namespace App\Entity;

use App\Repository\RoomStatusParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomStatusParticipantRepository::class)]
class RoomStatusParticipant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'datetime')]
    private $enteredRoomAt;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private $leftRoomAt;
    #[ORM\Column(type: 'boolean')]
    private $inRoom;
    #[ORM\ManyToOne(targetEntity: RoomStatus::class, inversedBy: 'roomStatusParticipants')]
    #[ORM\JoinColumn(nullable: false)]
    private $roomStatus;
    #[ORM\Column(type: 'text')]
    private $participantId;
    #[ORM\Column(type: 'text')]
    private $participantName;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $dominantSpeakerTime;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEnteredRoomAt(): ?\DateTimeInterface
    {
        return $this->enteredRoomAt;
    }
    public function getEnteredRoomAtwithTimeZone(?User $user): ?\DateTimeInterface
    {
        $data = $this->getEnteredRoomAtUTC();
        if (!$data) {
            return null;
        }
        if ($user && $user->getTimeZone()) {
            $localTimezone = new \DateTimeZone($user->getTimeZone());
        } else {
            $localTimezone = (new \DateTime())->getTimezone();
        }
        $data->setTimeZone($localTimezone);
        return $data;
    }
    public function getEnteredRoomAtUTC(): ?\DateTimeInterface
    {
        if (!$this->enteredRoomAt) {
            return null;
        }
        return new \DateTime($this->enteredRoomAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function setEnteredRoomAt(\DateTimeInterface $enteredRoomAt): self
    {
        $this->enteredRoomAt = $enteredRoomAt;

        return $this;
    }
    public function getLeftRoomAt(): ?\DateTimeInterface
    {
        return $this->leftRoomAt;
    }
    public function getLeftRoomAtwithTimeZone(?User $user): ?\DateTimeInterface
    {
        $data = $this->getLeftRoomAtUTC();
        if (!$data) {
            return null;
        }
        if ($user && $user->getTimeZone()) {
            $localTimezone = new \DateTimeZone($user->getTimeZone());
        } else {
            $localTimezone = (new \DateTime())->getTimezone();
        }
        $data->setTimeZone($localTimezone);
        return $data;
    }
    public function getLeftRoomAtUTC(): ?\DateTimeInterface
    {
        if (!$this->leftRoomAt) {
            return null;
        }
        return new \DateTime($this->leftRoomAt->format('Y-m-d H:i:s'), new \DateTimeZone('utc'));
    }
    public function setLeftRoomAt(?\DateTimeInterface $leftRoomAt): self
    {
        $this->leftRoomAt = $leftRoomAt;

        return $this;
    }
    public function getInRoom(): ?bool
    {
        return $this->inRoom;
    }
    public function setInRoom(bool $inRoom): self
    {
        $this->inRoom = $inRoom;

        return $this;
    }
    public function getRoomStatus(): ?RoomStatus
    {
        return $this->roomStatus;
    }
    public function setRoomStatus(?RoomStatus $roomStatus): self
    {
        $this->roomStatus = $roomStatus;

        return $this;
    }
    public function getParticipantId(): ?string
    {
        return $this->participantId;
    }
    public function setParticipantId(string $participantId): self
    {
        $this->participantId = $participantId;

        return $this;
    }
    public function getParticipantName(): ?string
    {
        return $this->participantName;
    }
    public function setParticipantName(string $participantName): self
    {
        $this->participantName = $participantName;

        return $this;
    }
    public function getDominantSpeakerTime(): ?int
    {
        return $this->dominantSpeakerTime;
    }
    public function setDominantSpeakerTime(?int $dominantSpeakerTime): self
    {
        $this->dominantSpeakerTime = $dominantSpeakerTime;

        return $this;
    }
}
