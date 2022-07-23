<?php

namespace App\Entity;

use App\Repository\SchedulingTimeUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchedulingTimeUserRepository::class)]
class SchedulingTimeUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'schedulingTimeUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    #[ORM\ManyToOne(targetEntity: SchedulingTime::class, inversedBy: 'schedulingTimeUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private $scheduleTime;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $accept;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
    public function getScheduleTime(): ?SchedulingTime
    {
        return $this->scheduleTime;
    }
    public function setScheduleTime(?SchedulingTime $scheduleTime): self
    {
        $this->scheduleTime = $scheduleTime;

        return $this;
    }
    public function getAccept(): ?int
    {
        return $this->accept;
    }
    public function setAccept(?int $accept): self
    {
        $this->accept = $accept;

        return $this;
    }
}
