<?php

namespace App\Service\Caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\CallerSession;
use App\Repository\CallerIdRepository;
use App\Entity\Rooms;
use App\Service\Callout\CalloutServiceDialSuccessfull;
use App\Service\Lobby\CreateLobbyUserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CallerPinService
{
    public function __construct(
        private readonly LoggerInterface               $loggger,
        private readonly EntityManagerInterface        $em,
        private readonly CreateLobbyUserService        $createLobbyUserService,
        private readonly ParameterBagInterface         $parameterBag,
        private readonly CalloutServiceDialSuccessfull $calloutServiceDialSuccessfull
    ) {
    }

    /**
     * Creates the caller session and the lobby user for a dial-in.
     * Closed rooms identify the caller by the personal PIN. Total open rooms have no participant list, so
     * the PIN is optional there and the caller is identified by the phone number instead.
     */
    public function createNewCallerSession(string $roomId, string $pin, string $callerId, bool $isSipVideo = false): ?CallerSession
    {
        $callerRoom = $this->em->getRepository(CallerRoom::class)->findOneBy(['callerId' => $roomId]);
        if (!$callerRoom) {
            $this->loggger->error('Room not found', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
            return null;
        }

        /** @var CallerIdRepository $callerIdRepository */
        $callerIdRepository = $this->em->getRepository(CallerId::class);
        $room               = $callerRoom->getRoom();
        $callInUser         = $callerIdRepository->findByRoomAndPin($room, $pin);
        if (!$callInUser) {
            $this->loggger->error('PIN not found for the room', ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]);
            return null;
        }

        if ($room->isE2EEActive()) {
            $this->loggger->info('SIP dial-in refused because E2EE is active for the room', ['roomId' => $roomId, 'callerId' => $callerId]);
            return null;
        }

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
            $this->loggger->error(
                'The Session is already used. Only one Session per PIN is allowed',
                ['roomId' => $roomId, 'callerId' => $callerId, 'pin' => $pin]
            );
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
            ->setCreatedAt(new \DateTimeImmutable())
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
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCallerId((string)$phoneNumber);
        $this->em->persist($callInUser);

        return $callInUser;
    }

    public function verifyCallerID(CallerSession $callerSession): bool
    {
        $callerID = $callerSession->getCallerId();
        try {
            $properties = $callerSession->getCaller()?->getUser()?->getSpezialProperties();
            /** @var int|string $key */
            $key = $this->parameterBag->get('SIP_CALLER_VERIVY_SPEZIAL_FIELD');

            if (isset($properties[$key])) {
                $phoneNumber = $properties[$key];
            }
        } catch (\Exception) {
            return false;
        }

        if (isset($phoneNumber) && $this->clean($callerID) === $this->clean($phoneNumber)) {
            return true;
        }

        return false;
    }

    public function clean(?string $string): ?string
    {
        $string = (string)$string;
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $res    = preg_replace('/[^0-9]/', '', $string); // Removes special chars.

        return $res;
    }
}
