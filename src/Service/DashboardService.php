<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;

class DashboardService
{
    public function categorizeRooms(array $rooms, User $user): array
    {
        $nowUtc = new \DateTime('now', new \DateTimeZone('utc'));
        $todayEndUtc = (new \DateTime('now', new \DateTimeZone('utc')))->setTime(23, 59, 59);

        $roomsFuture = [];
        $roomsNow = [];
        $roomsToday = [];
        $persistantRooms = [];
        $scheduledRooms = [];
        $roomIds = [];

        foreach ($rooms as $room) {
            $roomIds[] = $room->getId();
            if ($room->getPersistantRoom()) {
                $persistantRooms[] = $room;
                continue;
            }
            if ($room->getScheduleMeeting()) {
                $scheduledRooms[] = $room;
                continue;
            }
            if ($room->getStartUtc()) {
                $startTs = $room->getStartUtc()->getTimestamp();
                $endTs = $room->getEndDateUtc() ? $room->getEndDateUtc()->getTimestamp() : 0;
                $nowTs = $nowUtc->getTimestamp();
                $todayEndTs = $todayEndUtc->getTimestamp();

                if ($startTs < $nowTs && $endTs > $nowTs) {
                    $roomsNow[] = $room;
                }
                if ($endTs > $nowTs) {
                    $roomsFuture[$room->getStartwithTimeZone($user)->format('Ymd')][] = $room;
                }
                if ($endTs <= $todayEndTs && $startTs >= $nowTs) {
                    $roomsToday[] = $room;
                } elseif ($endTs >= $nowTs && $startTs <= $todayEndTs) {
                    $roomsToday[] = $room;
                }
            }
        }
        ksort($roomsFuture);

        return [
            'roomsFuture'      => $roomsFuture,
            'roomsNow'         => $roomsNow,
            'roomsToday'       => $roomsToday,
            'persistantRooms'  => $persistantRooms,
            'scheduledRooms'   => $scheduledRooms,
            'roomIds'          => $roomIds,
        ];
    }

    public function getRoomClosedForStartMap(array $rooms, User $user, array $roomStatusOpenMap): array
    {
        $now = new \DateTime('now', new \DateTimeZone('utc'));
        $result = [];
        foreach ($rooms as $room) {
            if (isset($roomStatusOpenMap[$room->getId()])) {
                continue;
            }
            if ($room->getPersistantRoom()) {
                continue;
            }
            if ($user === $room->getModerator()) {
                continue;
            }
            $start = $room->getStartUtc();
            $end = $room->getEndDateUtc();
            if ($start && $end) {
                $startWindow = (clone $start)->modify('-30min');
                if ($startWindow > $now || $end < $now) {
                    $result[$room->getId()] = sprintf(
                        'Der Beitritt ist nur von %s bis %s möglich',
                        $room->getStartwithTimeZone($user)->modify('-30min')->format('d.m.Y H:i'),
                        $room->getEndwithTimeZone($user)->format('d.m.Y H:i')
                    );
                }
            }
        }
        return $result;
    }
}
