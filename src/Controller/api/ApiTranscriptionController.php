<?php
declare(strict_types=1);

namespace App\Controller\api;

use App\Helper\BearerTokenAuthHelper;
use App\Repository\RoomsRepository;
use App\Service\TranscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiTranscriptionController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'API_TOKEN_BEARER_TRANSCRIPTION')]
        private string $transcriptionApiBearerToken,
        private readonly BearerTokenAuthHelper $bearerTokenAuthHelper,
        private readonly RoomsRepository $roomsRepository,
        private readonly TranscriptionService $transcriptionService,
    )
    {
    }

    #[Route('/api/v1/transcription', name: 'app_api_transcription_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $bearerToken = $this->bearerTokenAuthHelper->getBearerTokenFromRequest($request);
        if ($this->transcriptionApiBearerToken === '' || $bearerToken !== $this->transcriptionApiBearerToken) {
            return new JsonResponse(['error' => true, 'text' => 'No Bearer Token'], Response::HTTP_FORBIDDEN);
        }

        $roomUid = $request->get('roomUid');
        $room = $this->roomsRepository->findOneBy(['uidReal' => $roomUid]);
        if ($room === null) {
            return new JsonResponse(['error' => true, 'text' => 'Could not find room'], Response::HTTP_NOT_FOUND);
        }

        $text = $request->get('transcription');
        if ($text === null || (string)$text === '') {
            return new JsonResponse(['error' => true, 'text' => 'No transcription transmitted'], Response::HTTP_BAD_REQUEST);
        }

        $this->transcriptionService->addNewTranscription($room, (string)$text);

        return new JsonResponse(['error' => false, 'text' => 'Transcription saved successfully']);
    }
}
