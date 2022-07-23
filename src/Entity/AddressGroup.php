<?php

namespace App\Entity;

use App\Repository\AddressGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressGroupRepository::class)]
class AddressGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    #[ORM\Column(type: 'text')]
    private $name;
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'AddressGroupLeader')]
    #[ORM\JoinColumn(nullable: false)]
    private $leader;
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'AddressGroupMember')]
    private $member;
    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $updatedAt;
    #[ORM\Column(type: 'text', nullable: true)]
    private $indexer;
    public function __construct()
    {
        $this->member = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
    public function getLeader(): ?User
    {
        return $this->leader;
    }
    public function setLeader(?User $leader): self
    {
        $this->leader = $leader;

        return $this;
    }
    /**
     * @return Collection|User[]
     */
    public function getMember(): Collection
    {
        return $this->member;
    }
    public function addMember(User $member): self
    {
        if (!$this->member->contains($member)) {
            $this->member[] = $member;
        }

        return $this;
    }
    public function removeMember(User $member): self
    {
        $this->member->removeElement($member);

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
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    public function getIndexer(): ?string
    {
        return $this->indexer;
    }
    public function setIndexer(?string $indexer): self
    {
        $this->indexer = $indexer;

        return $this;
    }
}
