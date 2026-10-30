<?php

namespace App\Entity;

use App\Repository\LdapUserPropertiesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LdapUserPropertiesRepository::class)]
class LdapUserProperties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $ldapHost = null;

    #[ORM\Column(type: Types::TEXT, length: 65535)]
    private ?string $ldapDn = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'ldapUserProperties', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $rdn = null;

    #[ORM\Column(type: 'string', length: 64)]
    private ?string $ldapNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLdapHost(): ?string
    {
        return $this->ldapHost;
    }

    public function setLdapHost(string $ldapHost): self
    {
        $this->ldapHost = $ldapHost;

        return $this;
    }

    public function getLdapDn(): ?string
    {
        return $this->ldapDn;
    }

    public function setLdapDn(string $ldapDn): self
    {
        $this->ldapDn = $ldapDn;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRdn(): ?string
    {
        return $this->rdn;
    }

    public function setRdn(?string $rdn): self
    {
        $this->rdn = $rdn;

        return $this;
    }

    public function getLdapNumber(): ?string
    {
        return $this->ldapNumber;
    }

    public function setLdapNumber(?string $ldapNumber): self
    {
        $this->ldapNumber = $ldapNumber;

        return $this;
    }
}
