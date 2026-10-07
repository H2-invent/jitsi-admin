<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrowserCompability extends AbstractExtension
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('isFirefox', [$this, 'isFirefox']),
            new TwigFunction('isOSType', [$this, 'isOSType']),
        ];
    }

    public function isFirefox(): bool
    {
        $userAgent = strtolower($this->getUserAgent());

        return str_contains($userAgent, 'firefox/') || str_contains($userAgent, 'fxios/');
    }

    public function isOSType(string $osType): bool
    {
        $userAgent = strtolower($this->getUserAgent());

        return match (strtolower($osType)) {
            'windows' => str_contains($userAgent, 'windows'),
            'mac' => str_contains($userAgent, 'macintosh'),
            default => false,
        };
    }

    private function getUserAgent(): string
    {
        return $this->requestStack->getCurrentRequest()?->headers->get('User-Agent', '') ?? '';
    }
}
