<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;

class DashboardService
{
    /**
     * @param Rooms[] $rooms
     */
    public function categorizeRooms(array $rooms, User $user): array
    {
        $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('utc'));
        $todayEndUtc = (new \DateTimeImmutable('now', new \DateTimeZone('utc')))->setTime(23, 59, 59);

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
                $hasParticipants = $this->hasActiveParticipants($room);

                if ($hasParticipants || ($startTs < $nowTs && $endTs > $nowTs)) {
                    $roomsNow[] = $room;
                }
                if ($hasParticipants || ($endTs > $nowTs)) {
                    $roomsFuture[$room->getStartwithTimeZone($user)->format('Ymd')][] = $room;
                }
                if ($hasParticipants || ($endTs <= $todayEndTs && $startTs >= $nowTs) || ($endTs >= $nowTs && $startTs <= $todayEndTs)) {
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
        $now = new \DateTimeImmutable('now', new \DateTimeZone('utc'));
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
                $startWindow = $start->modify('-30min');
                if ($startWindow > $now || $end < $now) {
                    $result[$room->getId()] = sprintf(
                        'Der Beitritt ist nur von %s bis %s möglich',
                        (clone $room->getStartwithTimeZone($user))->modify('-30min')->format('d.m.Y H:i'),
                        (clone $room->getEndwithTimeZone($user))->format('d.m.Y H:i')
                    );
                }
            }
        }
        return $result;
    }

    private function hasActiveParticipants(Rooms $room): bool
    {
        foreach ($room->getRoomstatuses() as $roomStatus) {
            if ($roomStatus->getDestroyed() === true) {
                continue;
            }
            foreach ($roomStatus->getRoomStatusParticipants() as $participant) {
                if ($participant->getInRoom() === true) {
                    return true;
                }
            }
        }
        return false;
    }
}
