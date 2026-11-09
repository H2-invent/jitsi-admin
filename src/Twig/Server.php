<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\ServerUserManagment;

class Server
{
    public function __construct(private readonly ServerUserManagment $serverUserManagment)
    {
    }

    /**
     * @return \App\Entity\Server[]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getServer')]
    public function getServer(User $user): array
    {
        return $this->serverUserManagment->getServersFromUser($user);
    }

    /**
     * @return \App\Entity\Rooms[]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getActualConference')]
    public function getActualConference(\App\Entity\Server $server): array
    {
        return $this->serverUserManagment->getActualConference($server);
    }

    /**
     * @return \App\Entity\RoomStatusParticipant[]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getActualParticipants')]
    public function getActualParticipants(\App\Entity\Server $server): array
    {
        return $this->serverUserManagment->getActualParticipantsFromServer($server);
    }
}
