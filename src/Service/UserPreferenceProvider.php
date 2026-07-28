<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves UI preferences of the current viewer for use in JWT payloads,
 * templates and other places that need to render in the user's preferred shape.
 *
 * Each method follows the same precedence: explicit user choice (cookie / URL /
 * persisted on User) first, system default last. Worker and SIP contexts have
 * no request/security token, so they fall through to the system default.
 */
final readonly class UserPreferenceProvider
{
    public function __construct(
        private RequestStack          $requestStack,
        private ParameterBagInterface $parameters,
        private Security              $security,
    ) {}

    /**
     * Returns 'dark' or 'light'.
     *
     * Sources, in order:
     *   1. DARK_MODE cookie ('1' = dark)
     *   2. laf_darkmodeAsDefault parameter
     */
    public function getColorScheme(): string
    {
        $cookie = $this->requestStack->getCurrentRequest()?->cookies->get('DARK_MODE');
        if ($cookie !== null) {
            return $cookie == 1 ? 'dark' : 'light';
        }

        return $this->parameters->get('laf_darkmodeAsDefault') ? 'dark' : 'light';
    }

    /**
     * Returns the active locale code (e.g. 'de', 'en').
     *
     * Sources, in order:
     *   1. Request locale (set by JMS i18n routing from the URL prefix)
     *   2. kernel.default_locale
     */
    public function getLanguage(): string
    {
        return $this->requestStack->getCurrentRequest()?->getLocale()
            ?? $this->parameters->get('kernel.default_locale');
    }

    /**
     * Returns an IANA timezone identifier (e.g. 'Europe/Berlin').
     *
     * Sources, in order:
     *   1. Authenticated user's stored timezone (User::$timeZone)
     *   2. PHP runtime default (date_default_timezone_get)
     */
    public function getTimezone(): string
    {
        $user = $this->security->getUser();
        if ($user instanceof User && $user->getTimeZone()) {
            return $user->getTimeZone();
        }

        return date_default_timezone_get();
    }
}
