<?php

namespace App\Service\caller;

use App\Entity\CallerId;
use App\Entity\CallerRoom;
use App\Entity\CallerSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\CreateLobbyUserService;
use Doctrine\ORM\EntityManagerInterface;

class CallerPinService
{
    private $em;
    private $createLobbyUserService;

    public function __construct(EntityManagerInterface $entityManager, CreateLobbyUserService $createLobbyUserService)
    {
        $this->em = $entityManager;
        $this->createLobbyUserService = $createLobbyUserService;
    }

    public function getPin($roomId, $pin): ?CallerSession
    {
        $callerRoom = $this->em->getRepository(CallerRoom::class)->findOneBy(array('callerId' => $roomId));
        if (!$callerRoom) {
            return null;
//            return array(
//                'auth_ok' => false,
//                'links' => array()
//            );
        }
        $room = $callerRoom->getRoom();
        $callInUser = $this->em->getRepository(CallerId::class)->findByRoomAndPin($room, $pin);
        if (!$callInUser) {
            return null;
//            return array(
//                'auth_ok' => false,
//                'links' => array()
//            );
        }
        $lobbyUser = $this->createLobbyUserService->createNewLobbyUser($callInUser->getUser(), $callInUser->getRoom(), 'c');
        $session = $this->em->getRepository(CallerSession::class)->findOneBy(array('lobbyWaitingUser' => $lobbyUser));

        if ($session) {
            return null;
        }

        if (!$session) {
            $session = new CallerSession();
            $session->setSessionId(md5(uniqid()))
                ->setCreatedAt(new \DateTime())
                ->setAuthOk(false)
                ->setLobbyWaitingUser($lobbyUser);
            $this->em->persist($session);
            $this->em->flush();
        }
        return $session;

//        return array(
//            'auth_ok' => true,
//            'links' => array(
//                //todo hier der link rein
//                'session'=>'url',
//                'left'=> 'urlHangup'
//            )
//        );
    }
}