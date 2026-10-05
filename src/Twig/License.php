<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Server;
use App\Service\LicenseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class License
{
    public function __construct(private readonly LicenseService $licenseService)
    {
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'validateLicense')]
    public function validateLicense(Server $server): bool
    {
        return $this->licenseService->verify($server);
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'validateUntilLicense')]
    public function validateUntilLicense(Server $server): \DateTimeImmutable
    {
        return $this->licenseService->validUntil($server);
    }
}
