<?php

namespace App\Service\caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\CallerSession;
use App\Service\Callout\CalloutServiceDialSuccessfull;
use App\Service\Lobby\CreateLobbyUserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CallerPinService
{
    private $em;
    private $createLobbyUserService;
    private $loggger;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        LoggerInterface                       $logger,
        EntityManagerInterface                $entityManager,
        CreateLobbyUserService                $createLobbyUserService,
        ParameterBagInterface                 $parameterBag,
        private CalloutServiceDialSuccessfull $calloutServiceDialSuccessfull,
    )
    {
        $this->em = $entityManager;
        $this->createLobbyUserService = $createLobbyUserService;
        $this->loggger = $logger;
        $this->parameterBag = $parameterBag;
    }

    public function createNewCallerSession($roomId, $pin, $callerId, $isSipVideo = false): ?CallerSession
    {
        $callerRoom = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $roomId]);
        if (!$callerRoom) {
            $this->loggger->error('Room not found', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
            return null;
        }
        $room = $callerRoom->getRoom();
        $callInUser = $this->em->getRepository(CallerId::class)->findByRoomAndPin($room, $pin);
        if (!$callInUser) {
            $this->loggger->error('PIN not found for the room', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
            return null;
        }
        if ($callInUser->getCallerSession()) {
            $this->loggger->error('The Session is already used. Only one Session per PIN is allowed', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
            return null;
        }
        $lobbyUser = $this->createLobbyUserService->createNewLobbyUser($callInUser->getUser(), $callInUser->getRoom(), 'c', true);

        $this->em->getRepository(CallerSession::class)->findOneBy(['lobbyWaitingUser' => $lobbyUser]);
        $this->loggger->debug('We create a session for the caller', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
        $session = new CallerSession();
        $session->setSessionId(md5($roomId . $pin . uniqid()))
            ->setCreatedAt(new \DateTime())
            ->setAuthOk(false)
            ->setLobbyWaitingUser($lobbyUser)
            ->setCallerId($callerId)
            ->setShowName($lobbyUser->getShowName())
            ->setCaller($callInUser)
            ->setIsSipVideoUser($isSipVideo);
        $session->setCallerIdVerified($this->verifyCallerID($session));
        $this->em->persist($session);
        $this->em->flush();
        $lobbyUser->setCallerSession($session);
        $this->em->persist($lobbyUser);
        $this->em->flush();
        $this->loggger->debug('Session was successfully build', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
        $this->calloutServiceDialSuccessfull->dialSuccessfull($lobbyUser->getUser(), $room);
        return $session;
    }

    public function verifyCallerID(CallerSession $callerSession): bool
    {
        $callerID = $callerSession->getCallerId();
        try {
            $properties = $callerSession->getCaller()->getUser()->getSpezialProperties();
            $key = $this->parameterBag->get('SIP_CALLER_VERIVY_SPEZIAL_FIELD');

            if (isset($properties[$key])) {
                $phoneNumber = $properties[$key];
            }

        } catch (\Exception $exception) {
            return false;
        }
        if (isset($phoneNumber) && $this->clean($callerID) === $this->clean($phoneNumber)) {
            return true;
        }
        return false;
    }

    public function clean($string)
    {
        $string = str_replace(' ', '-', $string ?? ''); // Replaces all spaces with hyphens.

        $res = preg_replace('/[^0-9]/', '', $string ?? ''); // Removes special chars.
        return $res;
    }
}
