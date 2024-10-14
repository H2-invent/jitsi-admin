<?php

namespace App\Service\webhook;

use App\Entity\CallerSession;
use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Service\Lobby\LobbyUtils;
use App\Service\Summary\SendSummaryViaEmailService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RoomWebhookService
{
    private $em;
    private $logger;
    private $paramterBag;
    private LobbyUtils $lobbyUtils;

    public function __construct(
        LobbyUtils                         $lobbyUtils,
        EntityManagerInterface             $entityManager,
        LoggerInterface                    $logger,
        ParameterBagInterface              $parameterBag,
        private SendSummaryViaEmailService $sendSummaryViaEmailService,
        private ThemeService               $themeService
    )
    {
        $this->em = $entityManager;
        $this->logger = $logger;
        $this->paramterBag = $parameterBag;
        $this->lobbyUtils = $lobbyUtils;
    }

    public function startWebhook($data): ?string
    {
        $res = 'No event defined';;
        if (isset($data['event_name'])) {
            switch ($data['event_name']) {
                case 'muc-room-created':
                    $res = $this->roomCreated(
                        $data['room_name'],
                        $data['is_breakout'],
                        isset($data['breakout_room_id'])?$data['breakout_room_id']:null,
                        $data['room_jid'],
                        $data['created_at']
                    );
                    break;
                case 'muc-room-destroyed':
                    $res = $this->roomDestroyed(
                        $data['is_breakout'],
                        isset($data['breakout_room_id'])?$data['breakout_room_id']:null,
                        $data['room_jid'],
                        $data['destroyed_at']
                    );
                    break;
                case 'muc-occupant-joined':
                    $res = $this->roomParticipantJoin(
                        $data['is_breakout'],
                        isset($data['breakout_room_id'])?$data['breakout_room_id']:null,
                        $data['room_jid'],
                        $data['occupant']['occupant_jid'],
                        $data['occupant']['joined_at'],
                        isset($data['occupant']['name'])?$data['occupant']['name']:null

                    );
                    break;
                case 'muc-occupant-left':
                    $res = $this->roomParticipantLeft(
                        $data['is_breakout'],
                        $data['breakout_room_id'] ?? null,
                        $data['occupant']['occupant_jid'],
                        $data['occupant']['left_at'],
                        $data['occupant']['total_dominant_speaker_time'] ?? null
                    );
                    break;
                default:
                    $this->logger->error('Wrong event_name', ['event_name' => $data['event_name']]);
                    $res = 'Wrong event_name';
            }
        }
        return $res;
    }


    public function roomCreated(
        string $roomName,
        bool $isBreakout,
        ?string $breakoutRoomId,
        string $roomJid,
        int $createdAt
    ): ?string {
        try {


            try {
                $room = $this->em->getRepository(Rooms::class)->findRoomByCaseInsensitiveUid($roomName);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
            }

            if (!$room) {
                $text = 'Room name not found. This Room is externally controlled';
                $this->logger->debug($text, ['roomId' => $roomName]);
            }

            if ($isBreakout) {
                $this->logger->debug('This is a breakoutRoom', ['breakout_room_id' => $breakoutRoomId]);
                return 'Room is a breakout room; we don`t create a status';
            }

            if ($room) {
                $roomStatus = $this->em->getRepository(RoomStatus::class)->findCreatedRooms($room);
            } else {
                $roomStatus = $this->em->getRepository(RoomStatus::class)->findCreatedRoomsbyJitsiId($roomJid);
            }

            if ($roomStatus) {
                $text = 'Room already created';
                $this->logger->error($text, ['roomJidID' => $roomJid]);
                return $text;
            }

            if (!$roomStatus) {
                $roomStatus = new RoomStatus();
                $roomStatus->setCreatedAt(new \DateTime())
                    ->setJitsiRoomId($roomJid)
                    ->setRoom($room);
            }

            $roomStatus->setRoomCreatedAt(\DateTime::createFromFormat('U', $createdAt))
                ->setUpdatedAt(new \DateTime())
                ->setCreated(true);

            $this->em->persist($roomStatus);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return 'ERROR';
        }
        return null;
    }


    public function roomDestroyed(

        bool $isBreakout,
        ?string $breakoutRoomId,
        string $roomJid,
        int $destroyedAt
    ): ?string {
        try {


            if ($isBreakout) {
                $this->logger->debug('This is a breakoutRoom', [
                    'breakout_room_id' => $breakoutRoomId,
                    'room_jid' => $roomJid
                ]);
                return 'Room is a breakout room we don`t remove the main room';
            }

            $roomStatus = $this->em->getRepository(RoomStatus::class)->findCreatedRoomsbyJitsiId($roomJid);
            if (!$roomStatus) {
                $text = 'Room Jitsi ID not found';
                $this->logger->error($text, ['jitsiID' => $roomJid]);
                return $text;
            }

            if ($this->paramterBag->get('JITSI_EVENTS_HISTORY') == 0) {
                $statusOld = $this->em->getRepository(RoomStatus::class)->findBy(['jitsiRoomId' => $roomJid]);
                foreach ($statusOld as $data) {
                    $this->em->remove($data);
                    $this->em->flush();
                }
                return null;
            }

            $roomStatus->setDestroyedAt(\DateTime::createFromFormat('U', $destroyedAt))
                ->setUpdatedAt(new \DateTime())
                ->setDestroyed(true);

            $this->em->persist($roomStatus);
            $this->em->flush();

            if ($roomStatus->getRoom()) {
                $this->lobbyUtils->cleanLobby($roomStatus->getRoom());
            }

            foreach ($roomStatus->getRoomStatusParticipants() as $data2) {
                $data2->setLeftRoomAt(\DateTime::createFromFormat('U', $destroyedAt))
                    ->setInRoom(false);
                $this->em->persist($data2);
            }
            $this->em->flush();

            $this->clenRoomStatus($roomStatus);

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $exception->getMessage();
        }

        if ($this->themeService->getApplicationProperties('SEND_REPORT_AFTER_MEETING') === '1') {
            $this->sendSummaryViaEmailService->sendSummaryForRoom($roomStatus->getRoom());
        }

        return null;
    }

    public function roomParticipantJoin(
        ?bool $isBreakput,
        ?string $breakoutRoomName,
        string $roomJId,
    string $occupantJId,
        $joinedAt,
        ?string $occupantName = null,
    ): ?string
    {
        try {

            if ($isBreakput === true) {
                $this->logger->debug('This is a breakoutRoom', ['breakout_room_id ' =>$breakoutRoomName, 'room_jid' => $roomJId]);
                return 'Room is a breakout room we don`t join the participant';
            }
            $roomStatus = $this->em->getRepository(RoomStatus::class)->findCreatedRoomsbyJitsiId($roomJId);
            if (!$roomStatus) {
                $text = 'Room Jitsi ID not found';
                $this->logger->error($text, ['jitsiID' => $roomJId]);
                return $text;
            }

            $roomPart = $this->em->getRepository(RoomStatusParticipant::class)->findOneBy(['participantId' => $occupantJId]);
            if ($roomPart) {
                $text = 'The occupant already joind with the same occupant ID';
                $this->logger->error($text, ['occupantID' => $occupantJId]);
                return $text;
            }
            if (!$occupantName) {
                return 'NO_DATA';
            }
            $roomPart = new RoomStatusParticipant();
            $roomPart->setEnteredRoomAt(\DateTime::createFromFormat('U', $joinedAt))
                ->setInRoom(true)
                ->setParticipantId($occupantJId)
                ->setParticipantName($occupantName ?? 'No Data')
                ->setRoomStatus($roomStatus);
            $this->em->persist($roomPart);
            $this->em->flush();
            $roomStatus->addRoomStatusParticipant($roomPart);
            $this->em->persist($roomStatus);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return 'ERROR';
        }
        return null;
    }

    public function roomParticipantLeft(

        bool $isBreakout,
        ?string $breakoutRoomId,
        string $occupantJid,
        int $leftAt,
        ?int $totalDominantSpeakerTime = null
    ): ?string {
        try {

            if ($isBreakout) {
                $this->logger->debug('This is a breakoutRoom', ['breakout_room_id' => $breakoutRoomId]);
                return 'Room is a breakout room; we don`t remove the participant';
            }

            $roomPart = $this->em->getRepository(RoomStatusParticipant::class)->findOneBy(['participantId' => $occupantJid]);
            if (!$roomPart) {
                $text = 'Wrong occupant ID. The occupant is not in the database';
                $this->logger->error($text, ['occupantID' => $occupantJid]);
                return $text;
            }

            if ($roomPart->getInRoom() !== true) {
                $text = 'The occupant already left the room. It cannot leave the room twice';
                $this->logger->error($text, ['occupantID' => $occupantJid]);
                return $text;
            }

            $roomPart->setLeftRoomAt(\DateTime::createFromFormat('U', $leftAt))
                ->setInRoom(false)
                ->setDominantSpeakerTime($totalDominantSpeakerTime);

            $this->em->persist($roomPart);
            $this->em->flush();
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return 'ERROR';
        }

        return null;
    }

    public function clenRoomStatus(RoomStatus $roomStatus){
        if (!$roomStatus->getRoom()){
            foreach ($roomStatus->getRoomStatusParticipants() as $data){
                $this->em->remove($data);
            }
            $this->em->remove($roomStatus);
            $this->em->flush();
        }
    }
}
