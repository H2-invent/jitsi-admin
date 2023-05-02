<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Server;
use App\Service\LicenseService;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function GuzzleHttp\Psr7\str;

class License extends AbstractExtension
{
    private $licenseService;

    public function __construct(LicenseService $licenseService, TokenStorageInterface $tokenStorage, EntityManagerInterface $em)
    {
        $this->licenseService = $licenseService;
    }

    public function getFilters()
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
