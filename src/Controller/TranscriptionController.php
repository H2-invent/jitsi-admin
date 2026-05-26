<?php

namespace App\Controller;

use App\Entity\Transcription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TranscriptionController extends AbstractController
{
    #[Route('/transcription/{id}/download', name: 'app_transcription_download')]
    public function download(Transcription $transcription): Response
    {
        $fileName = "transcription_{$transcription->getRoom()->getName()}_{$transcription->getCreatedAt()->format('y-m-d_H-i-s')}.md";

        $response = new Response($transcription->getText());
        $response->headers->set('Content-Type', 'text/markdown');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition('attachment', $fileName));

        return $response;
    }
}
