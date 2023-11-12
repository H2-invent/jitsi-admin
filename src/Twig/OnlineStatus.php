<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\MessageService;
use App\Service\OnlineStatusService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function GuzzleHttp\Psr7\str;

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

    public function getOnlineStatus(User $user)
    {

        return $this->onlineStatusService->getUserStatus(user: $user) === 1 ? 'online' : 'offline';
    }

    public function getOnlineStatusString(User $user)
    {

        $state =  $this->onlineStatusService->getUserStatus(user: $user);
        return $state === 1?$this->translator->trans('status.online'):$this->translator->trans('status.offline');

    }


}
