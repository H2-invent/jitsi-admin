<?php

namespace App\Service\caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\CallerSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\CreateLobbyUserService;
use App\Service\webhook\RoomStatusFrontendService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CallerPinService
{
    private $em;
    private $createLobbyUserService;
    private $loggger;
    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, CreateLobbyUserService $createLobbyUserService)
    {
        $this->em = $entityManager;
        $this->createLobbyUserService = $createLobbyUserService;
        $this->loggger = $logger;
    }

    public function getPin($roomId, $pin, $callerId): ?CallerSession
    {
        $callerRoom = $this->em->getRepository(CallerRoom::class)->findOneBy(array('callerId' => $roomId));
        if (!$callerRoom) {
            $this->loggger->debug('Room not found',array('roomId'=>$roomId,'callerId'=>$callerId,'pin'=>$pin));
            return null;
        }
        $room = $callerRoom->getRoom();
        $callInUser = $this->em->getRepository(CallerId::class)->findByRoomAndPin($room, $pin);
        if (!$callInUser) {
            $this->loggger->debug('PIN not found for the room',array('roomId'=>$roomId,'callerId'=>$callerId,'pin'=>$pin));
            return null;
        }
        if ($callInUser->getCallerSession()) {
            $this->loggger->info('The Session is already used. Only one Session per PIN is allowed',array('roomId'=>$roomId,'callerId'=>$callerId,'pin'=>$pin));
            return null;
        }
        $lobbyUser = $this->createLobbyUserService->createNewLobbyUser($callInUser->getUser(), $callInUser->getRoom(), 'c');

        $this->em->getRepository(CallerSession::class)->findOneBy(array('lobbyWaitingUser' => $lobbyUser));
        $this->loggger->debug('We create a session for the caller',array('roomId'=>$roomId,'callerId'=>$callerId,'pin'=>$pin));
        $session = new CallerSession();
        $session->setSessionId(md5($roomId.$pin.uniqid()))
            ->setCreatedAt(new \DateTime())
            ->setAuthOk(false)
            ->setLobbyWaitingUser($lobbyUser)
            ->setCallerId($callerId)
            ->setShowName($lobbyUser->getShowName())
            ->setCaller($callInUser);
        $this->em->persist($session);
        $this->em->flush();
        $this->loggger->debug('Session was successfully build',array('roomId'=>$roomId,'callerId'=>$callerId,'pin'=>$pin));
        return $session;
    }
}