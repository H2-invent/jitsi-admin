<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\LobbyWaitungUser;
use App\Entity\User;
use App\Service\ParticipantSearchService;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

class Name extends AbstractExtension
{
    private ParticipantSearchService $participantSearchService;

    public function __construct(ParticipantSearchService $participantSearchService)
    {
        $this->participantSearchService = $participantSearchService;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('nameOfUser', [$this, 'nameOfUser']),
            new TwigFilter('nameOfUserNoSymbol', [$this, 'nameOfUserNoSymbol']),
        ];
    }

    public function nameOfUser(User|LobbyWaitungUser $user): Markup
    {
        if ($user instanceof LobbyWaitungUser) {
            $user = $user->getUser();
        }
        return new Markup(
            str_replace(
                ['<script>', '</script>'],
                ['<&lt;script&gt;', '&lt;/script&gt;'],
                $this->participantSearchService->buildShowInFrontendString($user)
            ),
            'utf-8'
        );
    }
    public function nameOfUserNoSymbol(User|LobbyWaitungUser $user): ?string
    {
        if ($user instanceof LobbyWaitungUser) {
            $userT = $user->getUser();
            return $user->getShowName();
        }
        return $this->participantSearchService->buildShowInFrontendStringNoString($user);
    }
}
