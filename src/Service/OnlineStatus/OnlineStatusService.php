<?php

namespace App\Service\OnlineStatus;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OnlineStatusService
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
    )
    {
    }

    public function getUserStatus(User $user):int
    {
        if ($user->getOnlineStatus()=== null){
            return $this->parameterBag->get('LAF_DEFAULT_ONLINE_STATUS');
        }else{
            return $user->getOnlineStatus();
        }
    }

    /**
     * Stored/manual online status of the user. This is not live presence; use
     * \App\Service\OnlineStatus\PresenceService for the current websocket connection state.
     */
    public function isUserOnline(User $user): bool
    {
        return $this->getUserStatus($user) === 1;
    }
}