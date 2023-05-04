<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\LobbyWaitungUser;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\MessageService;
use App\Service\ParticipantSearchService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use function GuzzleHttp\Psr7\str;

class Name extends AbstractExtension
{
    private $parameterBag;
    private ParticipantSearchService $participantSearchService;

    public function __construct(ParameterBagInterface $parameterBag, ParticipantSearchService $participantSearchService)
    {
        $this->parameterBag = $parameterBag;
        $this->participantSearchService = $participantSearchService;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('nameOfUser', [$this, 'nameOfUser']),
            new TwigFilter('nameOfUserNoSymbol', [$this, 'nameOfUserNoSymbol']),
        ];
    }

    public function nameOfUser(User|LobbyWaitungUser $user)
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
    public function nameOfUserNoSymbol(User|LobbyWaitungUser $user)
    {
        if ($user instanceof LobbyWaitungUser) {
            $userT = $user->getUser();
            return $user->getShowName();
        }
        return $this->participantSearchService->buildShowInFrontendStringNoString($user);
    }
}
