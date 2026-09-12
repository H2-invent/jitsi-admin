<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Server;
use App\Service\LicenseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class License extends AbstractExtension
{
    private $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('validateLicense', [$this, 'validateLicense']),
            new TwigFilter('validateUntilLicense', [$this, 'validateUntilLicense']),
        ];
    }

    public function validateLicense(Server $server): bool
    {
        return $this->licenseService->verify($server);
    }

    public function validateUntilLicense(Server $server): \DateTime
    {
        return $this->licenseService->validUntil($server);
    }
}
