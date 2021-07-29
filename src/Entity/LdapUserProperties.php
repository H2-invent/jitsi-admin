<?php

namespace App\Entity;

use App\Repository\LdapUserPropertiesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LdapUserPropertiesRepository::class)
 */
class LdapUserProperties
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $ldapHost;

    /**
     * @ORM\Column(type="text")
     */
    private $ldapDn;

    /**
     * @ORM\OneToOne(targetEntity=User::class, inversedBy="ldapUserProperties", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

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
}
