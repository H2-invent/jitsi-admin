<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $title;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $text;
    /** @var User|null */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;
    /** @var \DateTimeImmutable|null */
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;
    /** @var string|null */
    #[ORM\Column(type: 'text', nullable: true)]
    private $url;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
    public function getText(): ?string
    {
        return $this->text;
    }
    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
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
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
    public function getUrl(): ?string
    {
        return $this->url;
    }
    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
