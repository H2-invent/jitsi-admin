<?php

namespace App\Controller;

use Agence104\LiveKit\EgressServiceClient;
use App\Entity\Recording;
use App\Entity\Rooms;
use App\Repository\RecordingRepository;

use App\Service\livekit\EgressService;
use Doctrine\ORM\EntityManagerInterface;
use Livekit\DirectFileOutput;
use Livekit\EncodedFileOutput;
use Livekit\EncodedFileType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class StartEgressController extends AbstractController
{
    public function __construct(
        private RecordingRepository    $recordingRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger,
        private EgressService $egressService,
    )
    {
    }

    #[Route('/room/start/egress/{uidReal}/{template}', name: 'app_start_egress')]
    public function index(Request $request, ?Rooms $rooms, $template): Response
    {
        if (!$rooms || !$rooms->getServer()->isLiveKitServer() || $this->getUser() !== $rooms->getModerator()) {
            $this->logger->debug('Room not found');
            return new JsonResponse(['error' => true]);

        }
        return new JsonResponse($this->egressService->startEgress($rooms,$this->getUser(),$template));
    }

    #[Route('/room/stop/egress/{recordingId}', name: 'app_stop_egress')]
    public function stop(Request $request, ?Recording $recording): Response
    {

        if (!$recording || $recording->getUser() !== $this->getUser()) {
            throw new NotFoundHttpException('Recording not found');
        }
        return new JsonResponse($this->egressService->stopEgress($recording));
    }
}
