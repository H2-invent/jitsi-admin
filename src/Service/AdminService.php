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
use App\Repository\RoomStatusParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;

class AdminService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return array<int, array{date: \DateTimeImmutable, participants: int, rooms: int, participants_real: int}>
     */
    public function createChart(Server $server): array
    {
        $rooms = $this->em->getRepository(Rooms::class)->findBy(['server' => $server]);


        $chart     = [];
        $firstDate = new \DateTimeImmutable();
        $firstDate = $firstDate->modify('-30 days');
        $lastDate  = new \DateTimeImmutable();
        $lastDate  = $lastDate->modify('+30 days');
        /** @var RoomStatusParticipantRepository $participantRepository */
        $participantRepository = $this->em->getRepository(RoomStatusParticipant::class);
        $participants          = $participantRepository->findParticipantsByServer($server, $firstDate, $lastDate);

        for ($x = 0; $x <= 60; $x++) {
            $date = $firstDate->modify('+' . $x . 'days');
            $key  = (int) $date->format('Ymd');

            $entry = [
                'date'              => $date,
                'participants'      => 0,
                'rooms'             => 0,
                'participants_real' => 0,
            ];

            foreach ($rooms as $data) {
                if ($data->getScheduleMeeting() != true
                    && $data->getStart()
                    && !$data->getRepeaterProtoype()
                    && $data->getStart()->format('Ymd') === $date->format('Ymd')
                ) {
                    $entry['rooms']        = $entry['rooms'] + 1;
                    $entry['participants'] = $entry['participants'] + count($data->getUser());
                }
            }

            foreach ($participants as $p) {
                if ($p->getEnteredRoomAt()->format('Ymd') === $date->format('Ymd')) {
                    $entry['participants_real'] = $entry['participants_real'] + 1;
                }
            }

            $chart[$key] = $entry;
        }

        return $chart;
    }
}
