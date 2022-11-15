<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\Service\Summary\CreateSummaryService;
use App\UtilsHelper;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DownloadSummaryController extends JitsiAdminController
{
    #[Route('room/download/summary', name: 'app_download_sumary')]
    public function index(Request $request, CreateSummaryService $createSummaryService)
    {
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));

        if (!$room || !UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            throw new NotFoundHttpException('Room not found');
        }

        $res = $createSummaryService->createSummaryPdf($room);

        $res->stream($room->getName() . ".pdf", [
            "Attachment" => true
        ]);

    }
}