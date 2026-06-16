<?php

namespace App\Controller\api;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Service\livekit\LivekitJwtService;
use App\Service\ProvisionerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/api/v1/middleware')]
class ApiMiddlewareController extends AbstractController
{
    public function __construct(
        private ProvisionerService $provisionerService,
        private RoomsRepository $roomsRepository,
        private ServerRepository $serverRepository,
        private LivekitJwtService $livekitJwtService,
    )
    {
    }

    #[Route(path: '/room-deleted', name: 'app_api_middleware_room-deleted', methods: ['POST'])]
    public function roomDeleted(Request $request): Response
    {
        $host = $request->get('host');
        $key = $request->get('key');
        $jwt = $request->get('jwt');
        if ($host === null || $key === null || $jwt === null) {
            return new JsonResponse(['error' => true, 'text' => 'Parameters missing'], Response::HTTP_BAD_REQUEST);
        }

        $server = $this->serverRepository->findOneBy(['url' => $host, 'appId' => $key]);
        if ($server === null) {
            return new JsonResponse(['error' => true, 'text' => 'Server not found'], Response::HTTP_NOT_FOUND);
        }

        // if provisioning is not enabled, we don't have to do anything here
        if (!$server->isProvisioningEnabled()) {
            return new JsonResponse(['error' => false], Response::HTTP_OK);
        }

        $jwtDecrypted = $this->livekitJwtService->getDecryptedJwt($jwt, $server);
        if ($jwtDecrypted === null) {
            return new JsonResponse(['error' => true, 'text' => 'Could not decrypt and verify JWT'], Response::HTTP_NOT_FOUND);
        }

        $uid = explode('@', $jwtDecrypted['room'])[0];
        $room = $this->roomsRepository->findOneBy(['uid' => $uid]);
        if ($room === null) {
            return new JsonResponse(['error' => true, 'text' => 'Room not found '], Response::HTTP_NOT_FOUND);
        }

        $this->provisionerService->requestDeletion($room);

        return new JsonResponse(['error' => false], Response::HTTP_OK);
    }
}
