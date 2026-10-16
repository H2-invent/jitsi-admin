<?php

namespace App\Service\caller;

use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use Psr\Log\LoggerInterface;

/**
 * The dial-in number of a series conference belongs to the prototype room of the series while every
 * appointment of the series runs in a conference of its own. Nobody ever joins the prototype, so the
 * caller api would answer "the conference has not started yet" although an appointment is running.
 * Dialing a series number therefore continues in the appointment that takes place right now.
 */
class SeriesDialInResolver
{
    public function __construct(
        private RoomsRepository $roomsRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function resolveRoom(?Rooms $room): ?Rooms
    {
        if (!$room) {
            return null;
        }
        $repeater = $room->getRepeaterProtoype();
        if (!$repeater) {
            return $room;
        }
        $now = (new \DateTime())->getTimestamp();
        $runningAppointment = $this->roomsRepository->findRunningRoomOfRepeater($repeater, $now);
        if (!$runningAppointment) {
            return $room;
        }
        $this->logger->info(
            'The dial-in into a series conference is handed over to the running appointment',
            ['prototype' => $room->getId(), 'appointment' => $runningAppointment->getId()]
        );

        return $runningAppointment;
    }
}
