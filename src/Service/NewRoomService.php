<?php

namespace App\Service;

use App\Entity\Log;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewRoomService
{

    public function __construct(
        private RoomsRepository        $roomsRepository,
        private TranslatorInterface    $translator,
        private UrlGeneratorInterface  $urlGenerator,
        private ServerRepository       $serverRepository,
        private ServerUserManagment    $serverUserManagment,
        private RoomGeneratorService   $roomGeneratorService,
        private RequestStack           $requestStack,
        private SerializerInterface    $serializer,
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function newRoomService(Request $request, User $myUser): Rooms|Response
    {


        $servers = $this->serverUserManagment->getServersFromUser($myUser);

        $id = $request->get('id') ?? null;
        $edit = ($id !== null);

        if ($edit) {
            $room = $this->roomsRepository->findOneBy(['id' => $id]);
            if (!UtilsHelper::isAllowedToOrganizeRoom($myUser, $room)) {
                $this->requestStack->getSession()->getBag('flashes')->add('danger', $this->translator->trans('Keine Berechtigung'));
                return new RedirectResponse($this->urlGenerator->generate('dashboard'));
            }
            $sequence = $room->getSequence() + 1;
            $room->setSequence($sequence);
            if (!$room->getUidModerator()) {
                $room->setUidModerator(md5(uniqid('h2-invent', true)));
            }
            if (!$room->getUidParticipant()) {
                $room->setUidParticipant(md5(uniqid('h2-invent', true)));
            }
            $serverChoose = $room->getServer();
        } else {
            $serverChoose = null;

            if ($request->cookies->has('room_server')) {
                $server = $this->serverRepository->find($request->cookies->get('room_server'));
                if ($server && in_array($server, $servers)) {
                    $serverChoose = $server;
                }
            }

            if (count($servers) > 0) {
                $serverChoose = $servers[0];
            }

            $room = $this->roomGeneratorService->createRoom($myUser, $serverChoose);
        }


        if ($request->get('serverfake')) {
            $tmp = $this->serverRepository->find($request->get('serverfake'));
            if ($tmp) {
                $room->setServer($tmp);
            }
        }
        return $room;
    }

    public function roomChanged(Rooms $oldRoom, Rooms $newRoom): bool
    {
        return (
            $oldRoom->getStart() !== $newRoom->getStart()
            || $oldRoom->getDuration() !== $newRoom->getDuration()
            || $oldRoom->getName() !== $newRoom->getName()
            || $oldRoom->getAgenda() !== $newRoom->getAgenda()
            || $oldRoom->getPersistantRoom() !== $newRoom->getPersistantRoom()
        );
    }

    public function writeLogInDatabase(Rooms $roomold, Rooms $room, User $myUser)
    {

        if ($room->getCreator() !== $room->getModerator()) {
            $log = new Log();
            $exclude = array(
                'user',
                'server',
                'userAttributes',
                'subscribers',
                'schedulings',
                'waitinglists',
                'repeater',
                'repeaterProtoype',
                'favoriteUsers',
                'lobbyWaitungUsers',
                'roomstatuses',
                'callerRoom',
                'callerIds',
                'tag',
                'creator',
                'logs');
            $message = array(
                'roomId' => $room->getId(),
                'userName' => $myUser->getUid(),
                'state' => 'room Edit',
                'oldObject' => json_decode($this->serializer->serialize($roomold,
                    JsonEncoder::FORMAT,
                    [AbstractNormalizer::IGNORED_ATTRIBUTES => $exclude])),
                'newObject' => json_decode($this->serializer->serialize($room,
                    JsonEncoder::FORMAT,
                    [AbstractNormalizer::IGNORED_ATTRIBUTES => $exclude])),
            );
            $log->setCreatedAt(new \DateTime())
                ->setUserName($myUser->getUid())
                ->setMessage(json_encode($message))
                ->setUser($myUser)
                ->setRoom($room);

            $this->entityManager->persist($log);
            $this->entityManager->flush();
        }
    }
}