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
        $rooms = $this->em->getRepository(Rooms::class)->findBy(['server' => $server]);


        $chart = [];
        $firstDate = new \DateTime();
        $firstDate = date_modify($firstDate, '-30 days');
        $lastDate = new \DateTime();
        $lastDate = date_modify($lastDate, '+30 days');
        $participants = $this->em->getRepository(RoomStatusParticipant::class)->findParticipantsByServer($server, $firstDate, $lastDate);
        for ($x = 0; $x <= 60; $x++) {
            $d = clone $firstDate;
            $date = date_modify($d, '+' . $x . 'days');

            $chart[$date->format('Ymd')]['date'] = $date;
            $chart[$date->format('Ymd')]['participants'] = 0;
            $chart[$date->format('Ymd')]['rooms'] = 0;
            $chart[$date->format('Ymd')]['participants_real'] = 0;

            foreach ($rooms as $data) {
                if ($data->getScheduleMeeting() != true
                    && $data->getStart()
                    && !$data->getRepeaterProtoype()
                    && $data->getStart()->format('Ymd') === $date->format('Ymd')
                ) {
                    $chart[$date->format('Ymd')]['rooms'] = $chart[$date->format('Ymd')]['rooms'] + 1;
                    $chart[$date->format('Ymd')]['participants'] = $chart[$date->format('Ymd')]['participants'] + count($data->getUser());
                }
            }

            foreach ($participants as $p) {
                if ($p->getEnteredRoomAt()->format('Ymd') === $date->format('Ymd')) {
                    $chart[$date->format('Ymd')]['participants_real'] = $chart[$date->format('Ymd')]['participants_real'] + 1;
                }
            }
        }
        return $chart;
    }
}
