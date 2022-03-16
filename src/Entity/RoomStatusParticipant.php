<?php

namespace App\Entity;

use App\Repository\RoomStatusParticipantRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RoomStatusParticipantRepository::class)
 */
class RoomStatusParticipant
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $enteredRoomAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $leftRoomAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $inRoom;

    /**
     * @ORM\ManyToOne(targetEntity=RoomStatus::class, inversedBy="roomStatusParticipants")
     * @ORM\JoinColumn(nullable=false)
     */
    private $roomStatus;

    /**
     * @ORM\Column(type="text")
     */
    private $participantId;

    /**
     * @ORM\Column(type="text")
     */
    private $participantName;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnteredRoomAt(): ?\DateTimeInterface
    {
        return $this->enteredRoomAt;
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
}
