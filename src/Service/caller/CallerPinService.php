<?php

namespace App\Service\caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\CallerSession;
use App\Entity\Rooms;
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

    /**
     * Creates the caller session and the lobby user for a dial-in.
     * Closed rooms identify the caller by the personal PIN. Total open rooms have no participant list, so
     * the PIN is optional there and the caller is identified by the phone number instead.
     */
    public function createNewCallerSession($roomId, ?string $pin, $callerId, $isSipVideo = false): ?CallerSession
    {
        $callerRoom = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $roomId]);
        if (!$callerRoom) {
            $this->loggger->error('Room not found', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
            return null;
        }
        $room = $callerRoom->getRoom();
        if ($pin !== null && $pin !== '') {
            $callInUser = $this->em->getRepository(CallerId::class)->findByRoomAndPin($room, $pin);
            if (!$callInUser) {
                $this->loggger->error('PIN not found for the room', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
                return null;
            }
        } elseif ($room->getTotalOpenRooms()) {
            $callInUser = $this->createCallInUserFromPhoneNumber($room, $callerId);
            if (!$callInUser) {
                $this->loggger->error('No phone number was sent for the total open room', ['roomId' => $roomId, 'callerId' => $callerId]);
                return null;
            }
        } else {
            $this->loggger->error('A PIN is required. Only total open rooms allow a dial-in without a PIN', ['roomId' => $roomId, 'callerId' => $callerId]);
            return null;
        }
        if ($callInUser->getCallerSession()) {
            $this->loggger->error('The Session is already used. Only one Session per PIN is allowed', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
            return null;
        }
        $lobbyUser = $this->createLobbyUserService->createNewLobbyUser(
            $callInUser->getUser(),
            $callInUser->getRoom(),
            'c',
            true,
            $callInUser->getUser() ? null : $callInUser->getCallerId()
        );

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
        if ($lobbyUser->getUser()) {
            $this->calloutServiceDialSuccessfull->dialSuccessfull($lobbyUser->getUser(), $room);
        }
        return $session;
    }

    /**
     * Total open rooms have no participant list and therefore no prepared call-in user with a PIN.
     * The call-in user is created on the fly and carries the phone number instead of a PIN. It has no user.
     */
    private function createCallInUserFromPhoneNumber(Rooms $room, $phoneNumber): ?CallerId
    {
        if ($phoneNumber === null || $phoneNumber === '') {
            return null;
        }
        $callInUser = new CallerId();
        $callInUser->setRoom($room)
            ->setUser(null)
            ->setCreatedAt(new \DateTime())
            ->setCallerId((string)$phoneNumber);
        $this->em->persist($callInUser);

        return $callInUser;
    }

    public function verifyCallerID(CallerSession $callerSession): bool
    {
        $callerID = $callerSession->getCallerId();
        try {
            $properties = $callerSession->getCaller()?->getUser()?->getSpezialProperties();
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
