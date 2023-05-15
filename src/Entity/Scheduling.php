<?php

namespace App\Entity;

use App\Repository\SchedulingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchedulingRepository::class)]
class Scheduling
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $uid;
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;
    #[ORM\ManyToOne(targetEntity: Rooms::class, inversedBy: 'schedulings')]
    #[ORM\JoinColumn(nullable: false)]
    private $room;
    #[ORM\OneToMany(targetEntity: SchedulingTime::class, mappedBy: 'scheduling')]
    private $schedulingTimes;

    #[ORM\Column(nullable: true)]
    private ?bool $completedEmailSent = null;
    public function __construct()
    {
        $this->schedulingTimes = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
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
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;

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
    /**
     * @return Collection|SchedulingTime[]
     */
    public function getSchedulingTimes(): Collection
    {
        return $this->schedulingTimes;
    }
    public function addSchedulingTime(SchedulingTime $schedulingTime): self
    {
        if (!$this->schedulingTimes->contains($schedulingTime)) {
            $this->schedulingTimes[] = $schedulingTime;
            $schedulingTime->setScheduling($this);
        }

        return $this;
    }
    public function removeSchedulingTime(SchedulingTime $schedulingTime): self
    {
        if ($this->schedulingTimes->removeElement($schedulingTime)) {
            // set the owning side to null (unless already changed)
            if ($schedulingTime->getScheduling() === $this) {
                $schedulingTime->setScheduling(null);
            }
        }

        return $this;
    }

    public function isCompletedEmailSent(): ?bool
    {
        return $this->completedEmailSent;
    }

    public function setCompletedEmailSent(?bool $completedEmailSent): self
    {
        $this->completedEmailSent = $completedEmailSent;

        return $this;
    }
}
