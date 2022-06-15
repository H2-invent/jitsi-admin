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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CallerPinService
{
    private $em;
    private $createLobbyUserService;
    private $loggger;
    private ParameterBagInterface $parameterBag;
    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager, CreateLobbyUserService $createLobbyUserService, ParameterBagInterface $parameterBag)
    {
        $this->em = $entityManager;
        $this->createLobbyUserService = $createLobbyUserService;
        $this->loggger = $logger;
        $this->parameterBag = $parameterBag;
    }

    public function createNewCallerSession($roomId, $pin, $callerId): ?CallerSession
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
        $session->setCallerIdVerified($this->verifyCallerID($session));
        $this->em->persist($session);
        $this->em->flush();
        $lobbyUser->setCallerSession($session);
        $this->em->persist($lobbyUser);
        $this->em->flush();
        $this->loggger->debug('Session was successfully build',array('roomId'=>$roomId,'callerId'=>$callerId,'pin'=>$pin));
        return $session;
    }
    public function verifyCallerID(CallerSession $callerSession):bool{
        $callerID = $callerSession->getCallerId();
        try {
             $phoneNumber = $callerSession->getCaller()->getUser()->getSpezialProperties()[$this->parameterBag->get('SIP_CALLER_VERIVY_SPEZIAL_FIELD')];
        }catch (\Exception $exception){
            return false;
        }
        if ($this->clean($callerID) === $this->clean($phoneNumber)){
            return true;
        }
        return false;


    }
    public function clean($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        $res =  preg_replace('/[^0-9]/', '', $string); // Removes special chars.
        return $res;
    }

}