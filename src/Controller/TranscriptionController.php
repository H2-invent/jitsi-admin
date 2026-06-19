<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Transcription;
use App\Service\Transcription\TranscriptionService;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TranscriptionController extends AbstractController
{
    public function __construct(
        private readonly TranscriptionService $transcriptionService,
        private readonly LoggerInterface $logger,
    )
    {
    }

    #[Route('/room/transcription/{id}/download', name: 'app_transcription_download')]
    public function download(Transcription $transcription): Response
    {
        $user = $this->getUser();
        $moderator = $transcription->getRoom()?->getModerator();
        if ($user === null || $moderator === null || $user !== $moderator) {
            throw $this->createAccessDeniedException('Only moderators are allowed to download room transcriptions.');
        }

        $fileName = $transcription->getFileName();

        $response = new Response($transcription->getText());
        $response->headers->set('Content-Type', 'text/markdown');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition('attachment', $fileName));

        return $response;
    }

    #[Route('/room/transcription/{id}/remove', name: 'app_transcription_remove', methods: ['GET'])]
    public function remove(Transcription $transcription): JsonResponse
    {
        if ($transcription->getRoom()->getModerator() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Only moderators are allowed to remove room transcriptions.');
        }

        try {
            $this->transcriptionService->removeTranscription($transcription);
        } catch (Exception $e) {
            $this->logger->error('Error removing file', ['error' => $e->getMessage()]);

            return new JsonResponse(['error' => 'Error removing file: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['error' => false]);
    }


    #[Route('/room/transcriptions/modal/{room}', name: 'app_transcription_modal', methods: ['GET'])]
    public function modal(Rooms $room): Response
    {
        if ($room->getModerator() !== $this->getUser()) {
            throw $this->createNotFoundException('Room not found');
        }

        return $this->render('transcription/modal.html.twig', ['room' => $room]);
    }
}
