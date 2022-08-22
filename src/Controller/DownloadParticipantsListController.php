<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Helper\JitsiAdminController;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        if (!$room || $room->getModerator() !== $this->getUser()){
            throw new NotFoundHttpException('Room not found');
        }
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('documents/participantsList.html.twig', [
            'title' => $room->getName(),
            'room'=>$room
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();
        ob_end_clean();
        // Output the generated PDF to Browser (force download)
        $dompdf->stream($room->getName().".pdf", [
            "Attachment" => false
        ]);
    }
}
