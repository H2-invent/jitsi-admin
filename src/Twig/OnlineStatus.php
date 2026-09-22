<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\OnlineStatusService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OnlineStatus extends AbstractExtension
{
    public function __construct(
        private OnlineStatusService $onlineStatusService,
        private TranslatorInterface $translator,
    )
    {

    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getOnlineStatus', [$this, 'getOnlineStatus']),
            new TwigFunction('getOnlineStatusString', [$this, 'getOnlineStatusString']),
        ];
    }

    /**
     * @return string
     */
    public function getOnlineStatus(User $user): string
    {

        return $this->onlineStatusService->getUserStatus(user: $user) === 1 ? 'online' : 'offline';
    }

    /**
     * @return string
     */
    public function getOnlineStatusString(User $user): string
    {

        $state =  $this->onlineStatusService->getUserStatus(user: $user);
        return $state === 1?$this->translator->trans('status.online'):$this->translator->trans('status.offline');

    }


}
