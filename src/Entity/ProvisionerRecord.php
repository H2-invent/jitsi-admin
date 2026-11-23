<?php

namespace App\Entity;

use App\Repository\ProvisionerRecordRepository;
use App\Service\Provisioner\ProvisionerStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProvisionerRecordRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProvisionerRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false, enumType: ProvisionerStatus::class)]
    private ProvisionerStatus $status = ProvisionerStatus::IDLE;

    #[ORM\Column(nullable: false)]
    private int $retryAttempts = 0;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'provisionerRecords')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Rooms $room = null;

    #[ORM\OneToOne]
    private ?Server $provisionedServer = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ProvisionerStatus
    {
        return $this->status;
    }

    public function setStatus(ProvisionerStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function setRetryAttempts(int $retryAttempts): static
    {
        $this->retryAttempts = $retryAttempts;

        return $this;
    }

    public function getRoom(): ?Rooms
    {
        return $this->room;
    }

    public function setRoom(?Rooms $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getProvisionedServer(): ?Server
    {
        return $this->provisionedServer;
    }

    public function setProvisionedServer(?Server $provisionedServer): static
    {
        $this->provisionedServer = $provisionedServer;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): static
    {
        if ($this->updatedAt === null) {
            $this->createdAt = new \DateTimeImmutable();
            $this->setUpdatedAtValue();
        }

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): static
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
