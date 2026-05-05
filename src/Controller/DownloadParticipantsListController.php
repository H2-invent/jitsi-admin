<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use App\UtilsHelper;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DownloadParticipantsListController extends JitsiAdminController
{
    #[Route('room/download/participants/list', name: 'app_download_participants_list')]
    public function index(Request $request)
    {
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));
        if (!$room || !UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            throw new NotFoundHttpException('Room not found');
        }
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('fontDir', '../var/cache');
        $pdfOptions->set('fontCache', '../var/cache');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView(
            'documents/participantsList.html.twig',
            [
                'title' => $room->getName(),
                'room' => $room
            ]
        );

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        ob_end_clean();
        // Output the generated PDF to Browser (force download)


        $response =  new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="' . $room->getName() . '.pdf";');


        $response->sendHeaders();

        $response->setContent($dompdf->output());

        return $response;

    }
}
