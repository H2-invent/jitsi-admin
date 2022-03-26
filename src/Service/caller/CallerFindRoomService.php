<?php

namespace App\Service\caller;

use App\Entity\CallerRoom;
use Doctrine\ORM\EntityManagerInterface;

class CallerFindRoomService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function findRoom($id)
    {
        $caller = $this->em->getRepository(CallerRoom::class)->findOneBy(array('callerId' => $id));
        $now = (new \DateTime())->getTimestamp();
        if (!$caller) {
            return array('status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => array());
        }

        if ($caller->getRoom()->getStartTimestamp() > $now) {
            return array(
                'status' => 'HANGUP',
                'reason' => 'TO_EARLY',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime' => $caller->getRoom()->getEndTimestamp(),
                'links' => array()
            );
        }
        if ($caller->getRoom()->getEndTimestamp() < $now) {
            return array(
                'status' => 'HANGUP',
                'reason' => 'TO_LATE',
                'startTime' => $caller->getRoom()->getStartTimestamp(),
                'endTime' => $caller->getRoom()->getEndTimestamp(),
                'links' => array()
            );
        }
        return array(
            'status' => 'ACCEPTED',
            'startTime' => $caller->getRoom()->getStartTimestamp(),
            'endTime' => $caller->getRoom()->getEndTimestamp(),
            'roomName' => $caller->getRoom()->getName(),
            //todo hier die url rein
            'links' => array('pin' => 'url')
        );
    }

}