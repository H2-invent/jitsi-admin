<?php

namespace App\Service\caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CallerPinService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getPin($roomId, $pin)
    {
        $room = $this->em->getRepository(CallerRoom::class)->findOneBy(array('callerId'=>$roomId));
        if (!$room) {
            return array(
                'auth_ok' => false,
                'links' => array()
            );
        }
        $callInUser = $this->em->getRepository(CallerId::class)->findOneBy(array('room' => $room->getRoom(), 'callerId' => $pin));
        if (!$callInUser) {
            return array(
                'auth_ok' => false,
                'links' => array()
            );
        }
        //todo hier den Lobbywaitinguser bauen

        return array(
            'auth_ok' => true,
            'links' => array(
                //todo hier der link rein
                'session'=>'url',
                'left'=> 'urlHangup'
            )
        );

    }
    public function createLobbyUser(User $user, Rooms $rooms){

    }
}