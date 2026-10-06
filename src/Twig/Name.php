<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\LobbyWaitungUser;
use App\Entity\User;
use App\Service\ParticipantSearchService;
use Twig\Markup;

class Name
{
    public function __construct(private readonly ParticipantSearchService $participantSearchService)
    {
    }

    #[\Twig\Attribute\AsTwigFilter(name: 'nameOfUser')]
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

    #[\Twig\Attribute\AsTwigFilter(name: 'nameOfUserNoSymbol')]
    public function nameOfUserNoSymbol(User|LobbyWaitungUser $user): ?string
    {
        if ($user instanceof LobbyWaitungUser) {
            $userT = $user->getUser();
            return $user->getShowName();
        }
        return $this->participantSearchService->buildShowInFrontendStringNoString($user);
    }
}
