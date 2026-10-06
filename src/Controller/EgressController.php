<?php

namespace App\Controller;

use App\Entity\Recording;
use App\Entity\Rooms;
use App\Service\Livekit\EgressService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EgressController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EgressService   $egressService,
    ) {
    }

    #[Route('/room/start/egress/{uidReal}/{template}', name: 'app_start_egress')]
    public function index(?Rooms $rooms, string $template): Response
    {
        if (!$rooms || !$rooms->getServer()->isLiveKitServer() || $this->getUser() !== $rooms->getModerator()) {
            $this->logger->debug('Room not found');
            return new JsonResponse(['error' => true]);
        }

        return new JsonResponse($this->egressService->startEgress($rooms, $this->getUser(), $template));
    }

    #[Route('/room/stop/egress/{recordingId}', name: 'app_stop_egress')]
    public function stop(?Recording $recording): Response
    {
        if (!$recording || $recording->getUser() !== $this->getUser()) {
            throw new NotFoundHttpException('Recording not found');
        }

        return new JsonResponse($this->egressService->stopEgress($recording));
    }
}
