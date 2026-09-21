<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\User;
use App\Service\ServerUserManagment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Server extends AbstractExtension
{
    private ServerUserManagment $serverUserManagment;
    public function __construct(ServerUserManagment $serverUserManagment)
    {
        $this->serverUserManagment = $serverUserManagment;
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getServer', [$this, 'getServer']),
            new TwigFunction('getActualConference', [$this, 'getActualConference']),
            new TwigFunction('getActualParticipants', [$this, 'getActualParticipants']),
        ];
    }

    /**
     * @return \App\Entity\Server[]
     */
    public function getServer(User $user)
    {

        return $this->serverUserManagment->getServersFromUser($user);
    }
    /**
     * @return \App\Entity\Rooms[]
     */
    public function getActualConference(\App\Entity\Server $server)
    {

        return $this->serverUserManagment->getActualConference($server);
    }
    /**
     * @return \App\Entity\RoomStatusParticipant[]
     */
    public function getActualParticipants(\App\Entity\Server $server)
    {
        return $this->serverUserManagment->getActualParticipantsFromServer($server);
    }
}
