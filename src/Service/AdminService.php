<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\RoomStatusParticipant;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;

class AdminService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function createChart(Server $server)
    {
        $chart = [];
        $firstDate = new \DateTime();
        $firstDate = date_modify($firstDate, '-30 days');
        $lastDate = new \DateTime();
        $lastDate = date_modify($lastDate, '+30 days');
        for ($x = 0; $x <= 60; $x++) {
            $d = clone $firstDate;
            $date = date_modify($d, '+' . $x . 'days');

            $chart[$date->format('Ymd')]['date'] = $date;
            $chart[$date->format('Ymd')]['participants'] = 0;
            $chart[$date->format('Ymd')]['rooms'] = 0;
            $chart[$date->format('Ymd')]['participants_real'] = 0;
        }

        $rooms = $this->em->getRepository(Rooms::class)->findRoomsWithUserCountForServer($server);
        foreach ($rooms as $room) {
            $dateKey = $room['start']->format('Ymd');
            if (isset($chart[$dateKey])) {
                $chart[$dateKey]['rooms'] = $chart[$dateKey]['rooms'] + 1;
                $chart[$dateKey]['participants'] = $chart[$dateKey]['participants'] + (int)$room['participantCount'];
            }
        }

        $participants = $this->em->getRepository(RoomStatusParticipant::class)->findParticipantsByServer($server, $firstDate, $lastDate);
        foreach ($participants as $participant) {
            $dateKey = $participant->getEnteredRoomAt()->format('Ymd');
            if (isset($chart[$dateKey])) {
                $chart[$dateKey]['participants_real'] = $chart[$dateKey]['participants_real'] + 1;
            }
        }

        return $chart;
    }
}
