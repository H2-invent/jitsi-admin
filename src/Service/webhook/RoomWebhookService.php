<?php

namespace App\Service\webhook;

use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class RoomWebhookService
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    public function startWebhook($data): bool
    {
        $res = false;
        if (isset($data['event_name'])) {
            switch ($data['event_name']) {
                case 'muc-room-created':
                    $res = $this->roomCreated($data);
                    break;
                case 'muc-room-destroyed':
                    $res = $this->roomDestroyed($data);
                    break;
                case 'muc-occupant-joined':
                    $res = $this->roomParticipantJoin($data);
                    break;
                case 'muc-occupant-left':
                    $res = $this->roomParticipantLeft($data);
                    break;
                default:
                    $this->logger->error('Wrong event_name', array('event_name' => $data['event_name']));
            }
        }
        return $res;
    }


    public function roomCreated($data): bool
    {
        try {
            if ($data['event_name'] !== "muc-room-created") {
                $this->logger->error('Wrong event_name', array('roomId' => $data['event_name']));
                return false;
            }
            $room = $this->em->getRepository(Rooms::class)->findOneBy(array('uid' => $data['room_name']));
            if (!$room) {
                $this->logger->error('Room name not found', array('roomId' => $data['room_name']));
                return false;
            }

            $roomStatus = $this->em->getRepository(RoomStatus::class)->findOneBy(array('room' => $room, 'jitsiRoomId' => $data['room_jid']));
            if ($roomStatus) {
                $this->logger->error('Room already created', array('roomJidID' => $data['room_jid']));
                return false;
            }
            $oldRooms = $this->em->getRepository(RoomStatus::class)->findBy(array('room'=>$room));
            foreach ($oldRooms as $i){
                $i->setDestroyedAt(new \DateTime())
                    ->setDestroyed(true)
                ->setUpdatedAt(new \DateTime());
                $this->em->persist($i);
            }
            $this->em->flush();

            if (!$roomStatus) {
                $roomStatus = new RoomStatus();
                $roomStatus->setCreatedAt(new \DateTime())
                    ->setJitsiRoomId($data['room_jid'])
                    ->setRoom($room);
            }

            $roomStatus->setRoomCreatedAt(\DateTime::createFromFormat('U', $data['created_at']))
                ->setUpdatedAt(new \DateTime())
                ->setCreated(true);
            $this->em->persist($roomStatus);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
        return true;
    }

    public function roomDestroyed($data): bool
    {
        try {
            if ($data['event_name'] !== "muc-room-destroyed") {
                $this->logger->error('Wrong event_name', array('roomId' => $data['event_name']));
                return false;
            }
            $roomStatus = $this->em->getRepository(RoomStatus::class)->findOneBy(array('jitsiRoomId' => $data['room_jid']));
            if (!$roomStatus) {
                $this->logger->error('Room Jitsi ID name not found', array('jitsiID' => $data['room_jid']));
                return false;
            }

            if ($roomStatus->getDestroyed()) {
                $this->logger->error('Room is already destroyed', array('jitsiID' => $data['room_jid']));
                return false;
            }
            $roomStatus->setDestroyedAt(\DateTime::createFromFormat('U', $data['destroyed_at']))
                ->setUpdatedAt(new \DateTime())
                ->setDestroyed(true);
            $this->em->persist($roomStatus);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
        return true;
    }

    public function roomParticipantJoin($data): bool
    {
        try {
            if ($data['event_name'] !== "muc-occupant-joined") {
                $this->logger->error('Wrong event_name', array('roomId' => $data['event_name']));
                return false;
            }
            $roomStatus = $this->em->getRepository(RoomStatus::class)->findOneBy(array('jitsiRoomId' => $data['room_jid']));
            if (!$roomStatus) {
                $this->logger->error('Room Jitsi ID name not found', array('jitsiID' => $data['room_jid']));
                return false;
            }
            $roomPart = $this->em->getRepository(RoomStatusParticipant::class)->findOneBy(array('roomStatus' => $roomStatus, 'participantId' => $data['occupant']['occupant_jid']));
            if ($roomPart) {
                $this->logger->error('Wrong Occupate ID. The Occupant is not in the database', array('occupantID' => $data['occupant']['occupant_jid']));
                return false;
            }
            $roomPart = new RoomStatusParticipant();
            $roomPart->setEnteredRoomAt(\DateTime::createFromFormat('U', $data['occupant']['joined_at']))
                ->setInRoom(true)
                ->setParticipantId($data['occupant']['occupant_jid'])
                ->setParticipantName($data['occupant']['name'])
                ->setRoomStatus($roomStatus);
            $this->em->persist($roomPart);
            $this->em->flush();
            $roomStatus->addRoomStatusParticipant($roomPart);
            $this->em->persist($roomStatus);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
        return true;
    }

    public function roomParticipantLeft($data): bool
    {
        try {
            if ($data['event_name'] !== "muc-occupant-left") {
                $this->logger->error('Wrong event_name', array('roomId' => $data['event_name']));
                return false;
            }

            $roomPart = $this->em->getRepository(RoomStatusParticipant::class)->findOneBy(array('participantId' => $data['occupant']['occupant_jid']));
            if (!$roomPart) {
                $this->logger->error('Wrong Occupate ID. The Occupant is not in the database', array('occupantID' => $data['occupant']['occupant_jid']));

                return false;
            }
            $roomPart->setLeftRoomAt(\DateTime::createFromFormat('U', $data['occupant']['left_at']))
                ->setInRoom(false);
            $this->em->persist($roomPart);
            $this->em->flush();

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return false;
        }
        return true;
    }
}