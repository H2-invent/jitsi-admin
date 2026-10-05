<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\OnlineStatusService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OnlineStatus
{
    public function __construct(
        private readonly OnlineStatusService $onlineStatusService,
        private readonly TranslatorInterface $translator,
    )
    {

    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getOnlineStatus')]
    public function getOnlineStatus(User $user): string
    {

        return $this->onlineStatusService->getUserStatus(user: $user) === 1 ? 'online' : 'offline';
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'getOnlineStatusString')]
    public function getOnlineStatusString(User $user): string
    {

        $state =  $this->onlineStatusService->getUserStatus(user: $user);
        return $state === 1?$this->translator->trans('status.online'):$this->translator->trans('status.offline');

    }


}
