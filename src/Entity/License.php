<?php

namespace App\Entity;

use App\Repository\LicenseRepository;
use App\Service\LicenseService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\Types\This;

#[ORM\Entity(repositoryClass: LicenseRepository::class)]
class License
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $licenseKey;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $license;
    /** @var \DateTimeImmutable|null */
    #[ORM\Column(type: 'datetime_immutable')]
    private $validUntil;
    /** @var string|null */
    #[ORM\Column(type: 'text')]
    private $url;
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getLicenseKey(): ?string
    {
        return $this->licenseKey;
    }
    public function setLicenseKey(string $licenseKey): self
    {
        $this->licenseKey = $licenseKey;

        return $this;
    }
    public function getLicense(): ?string
    {
        return $this->license;
    }
    public function setLicense(string $license): self
    {
        $this->license = $license;

        return $this;
    }
    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }
    public function setValidUntil(\DateTimeImmutable $validUntil): self
    {
        $this->validUntil = $validUntil;

        return $this;
    }
    public function getUrl(): ?string
    {
        return $this->url;
    }
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}
