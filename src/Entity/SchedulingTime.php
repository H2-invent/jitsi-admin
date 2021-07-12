<?php

namespace App\Entity;

use App\Repository\SchedulingTimeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SchedulingTimeRepository::class)
 */
class SchedulingTime
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
    private $time;

    /**
     * @ORM\ManyToOne(targetEntity=Scheduling::class, inversedBy="schedulingTimes")
     * @ORM\JoinColumn(nullable=false)
     */
    private $scheduling;

    /**
     * @ORM\OneToMany(targetEntity=SchedulingTimeUser::class, mappedBy="scheduleTime")
     */
    private $schedulingTimeUsers;

    public function __construct()
    {
        $this->schedulingTimeUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTime(): ?\DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getScheduling(): ?Scheduling
    {
        return $this->scheduling;
    }

    public function setScheduling(?Scheduling $scheduling): self
    {
        $this->scheduling = $scheduling;

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
            $schedulingTimeUser->setScheduleTime($this);
        }

        return $this;
    }

    public function removeSchedulingTimeUser(SchedulingTimeUser $schedulingTimeUser): self
    {
        if ($this->schedulingTimeUsers->removeElement($schedulingTimeUser)) {
            // set the owning side to null (unless already changed)
            if ($schedulingTimeUser->getScheduleTime() === $this) {
                $schedulingTimeUser->setScheduleTime(null);
            }
        }

        return $this;
    }
}
