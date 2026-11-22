<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;

class BrowserCompability
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'isFirefox')]
    public function isFirefox(): bool
    {
        $userAgent = strtolower($this->getUserAgent());

        return str_contains($userAgent, 'firefox/') || str_contains($userAgent, 'fxios/');
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'isOSType')]
    public function isOSType(string $osType): bool
    {
        $userAgent = strtolower($this->getUserAgent());

        return match (strtolower($osType)) {
            'windows' => str_contains($userAgent, 'windows'),
            'mac'     => str_contains($userAgent, 'macintosh'),
            default   => false,
        };
    }

    private function getUserAgent(): string
    {
        return $this->requestStack->getCurrentRequest()?->headers->get('User-Agent', '') ?? '';
    }
}
