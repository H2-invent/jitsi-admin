<?php

namespace App\Controller\Api;

use App\Repository\CallerIdRepository;
use App\Repository\CallerRoomRepository;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/v1/roomkit')]
class RoomkitController extends AbstractController
{
    public function __construct(
        private readonly CallerRoomRepository $callerRoomRepository,
        private readonly CallerIdRepository $callerIdRepository,
        private readonly RoomService $roomService,
        #[Autowire('%laf_showNameInConference%')]
        private readonly string $showNameInConference,
        #[Autowire('%env(ROOMKIT_SECRET)%')]
        private readonly string $roomkitSecret,
    ) {
    }

    /**
     * Returns the room join data including room name + JWT + secret
     */
    #[Route(
        '/join-data/{roomNumber}/{pin}',
        name: 'getJoinData',
        requirements: ['roomNumber' => '\d+', 'pin' => '\d+'],
        methods: ['GET']
    )]
    public function getJoinData(Request $request, string $roomNumber, string $pin): Response
    {
        if (!$this->isAuthorized($request)) {
            return new JsonResponse(['authorized' => false], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->callerRoomRepository->findOneBy(['callerId' => $roomNumber])?->getRoom();
        $callerId = $room ? $this->callerIdRepository->findByRoomAndPin($room, $pin) : null;

        if (!$room || !$callerId) {
            return new JsonResponse(['error' => 'Room or PIN not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $callerId->getUser();
        $userName = $user->getFormatedName($this->showNameInConference);

        $joinData = [
            'roomName' => $this->roomService->getRoomName($room),
            'jwt'      => $this->roomService->generateJwt($room, $user, $userName),
            'secret'   => $this->roomService->generateEncryptedSecret($room->getServer()),
        ];

        return new JsonResponse($joinData);
    }

    private function isAuthorized(Request $request): bool
    {
        return $this->roomkitSecret !== ''
            && hash_equals('Bearer ' . $this->roomkitSecret, (string) $request->headers->get('Authorization'));
    }
}
