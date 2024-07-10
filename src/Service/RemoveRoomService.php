<?php

namespace App\Service;

use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoveRoomService
{

    public function __construct(
        private EntityManagerInterface $em,
        private UserService            $userService,
        private LoggerInterface        $logger,
    )
    {

    }


    public function deleteRoom(Rooms $room)
    {
        try {
            foreach ($room->getUser() as $user) {
                if (!$room->getRepeater()) {
                    $this->userService->removeRoom($user, $room);
                }
                $room->removeUser($user);
                $this->em->persist($room);
            }
            if ($room->getRepeater()) {
                $room->setRepeater(null);
            }
            $this->em->persist($room);
            $room->setModerator(null);
            foreach ($room->getFavoriteUsers() as $data) {
                $room->removeFavoriteUser($data);
            }
            $this->em->persist($room);
            foreach ($room->getLobbyWaitungUsers() as $data) {
                $room->removeLobbyWaitungUser($data);
            }
            $this->em->persist($room);
            foreach ($room->getSubscribers() as $data) {
                $room->removeSubscriber($data);
                $this->em->remove($data);
            }
            $this->em->persist($room);
            foreach ($room->getWaitinglists() as $data) {
                $room->removeWaitinglist($data);
                $this->em->remove($data);
            }
            foreach ($room->getCallerIds() as $data) {
                $this->em->remove($data);
                $room->removeCallerId($data);
            }
            $this->em->persist($room);
            $this->em->flush();

            if ($room->getCallerRoom()) {
                $callerRoom = $room->getCallerRoom();
                $this->em->remove($callerRoom);
                $this->em->flush();
            }
            if ($room->getCalloutSessions()->count() > 0) {
                foreach ($room->getCalloutSessions() as $session) {
                    $this->em->remove($session);
                }
                $this->em->flush();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        return true;
    }
}
