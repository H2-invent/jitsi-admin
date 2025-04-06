<?php

namespace App\Controller\api;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiMoveRoomToOtherServerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface    $entityManager,
        private readonly RoomsRepository  $roomsRepository,
        private readonly ServerRepository $serverRepository,
    )
    {
    }

    #[Route('/api/v1/room/move', name: 'app_api_move_room_to_other_server', methods: ['POST'])]
    public function index(Request $request): Response
    {
        $apiKey = $request->headers->get('Authorization');
        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
        $room = $this->roomsRepository->findOneBy(['uidReal' => $request->get('uid')]);
        if (!$room) {
            return new JsonResponse(['error' => true, 'message' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }
        if ($room->getServer()->getApiKey() !== $apiKey) {
            return new JsonResponse(['error' => true, 'message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $newServer = $this->serverRepository->findOneBy(['id' => $request->get('server'), 'apiKey' => $apiKey]);
        if (!$newServer) {
            return new JsonResponse(['error' => true, 'message' => 'New Server not found'], Response::HTTP_NOT_FOUND);
        }
       $room->setServer($newServer);
        $this->entityManager->persist($room);
        $this->entityManager->flush();
        return new JsonResponse(['error' => false, 'message' => 'Room moved'], Response::HTTP_OK);

    }
}
