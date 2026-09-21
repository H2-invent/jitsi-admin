<?php

namespace App\Service;

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
            /** @var int $defaultStatus */
            $defaultStatus = $this->parameterBag->get('LAF_DEFAULT_ONLINE_STATUS');
            return $defaultStatus;
        }else{
            return $user->getOnlineStatus();
        }
    }
}