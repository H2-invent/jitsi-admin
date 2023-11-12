<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $title;
    #[ORM\OneToMany(targetEntity: Rooms::class, mappedBy: 'tag')]
    private $rooms;
    #[ORM\Column(type: 'boolean')]
    private $disabled = false;
    #[ORM\Column(type: 'integer', nullable: true)]
    private $priority;
    #[ORM\Column(type: 'text', nullable: true)]
    private $color;
    #[ORM\Column(type: 'text', nullable: true)]
    private $backgroundColor;

    #[ORM\ManyToMany(targetEntity: Server::class, mappedBy: 'tag')]
    private Collection $servers;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->servers = new ArrayCollection();
    }
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
    /**
     * @return Collection<int, Rooms>
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }
    public function addRoom(Rooms $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms[] = $room;
            $room->setTag($this);
        }

        return $this;
    }
    public function removeRoom(Rooms $room): self
    {
        if ($this->rooms->removeElement($room)) {
            // set the owning side to null (unless already changed)
            if ($room->getTag() === $this) {
                $room->setTag(null);
            }
        }

        return $this;
    }
    public function getDisabled(): ?bool
    {
        return $this->disabled;
    }
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }
    public function getPriority(): ?int
    {
        return $this->priority;
    }
    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }
    public function getColor(): ?string
    {
        return $this->color;
    }
    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }
    public function setBackgroundColor(?string $backgroundColor): self
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * @return Collection<int, Server>
     */
    public function getServers(): Collection
    {
        return $this->servers;
    }

    public function addServer(Server $server): static
    {
        if (!$this->servers->contains($server)) {
            $this->servers->add($server);
            $server->addServer($this);
        }

        return $this;
    }

    public function removeServer(Server $server): static
    {
        if ($this->servers->removeElement($server)) {
            $server->removeServer($this);
        }

        return $this;
    }
}
