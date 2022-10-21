<?php

namespace App\Service\Summary;

use App\Entity\Rooms;
use App\Service\ThemeService;
use App\Service\Whiteboard\WhiteboardJwtService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class CreateSummaryService
{
    public function __construct(
        private Environment          $environment,
        private HttpClientInterface  $httpClient,
        private ThemeService         $themeService,
        private WhiteboardJwtService $whiteboardJwtService)
    {
    }


    public function createSummary(Rooms $room):string
    {
        $res = $this->createHeader($room);
        $res .= $this->createWhiteBoardSummary($room);
        $res .= $this->createEtherpadExport($room);
        return $this->environment->render('documents/sumary/template.html.twig', array('text' => $res, 'title' => $room->getName()));
    }

    public function createSummaryPdf(Rooms $room):?Dompdf
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        // Retrieve the HTML generated in our twig file
        $html = $this->createSummary($room);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        ob_end_clean();

        // Output the generated PDF to Browser (force download)
       return $dompdf;
    }

    public function createHeader(Rooms $rooms): string
    {
        return $this->environment->render('documents/sumary/header.html.twig', array('room' => $rooms));


    }

    public function createWhiteBoardSummary(Rooms $room): ?string
    {
        try {
            $url = $this->themeService->getApplicationProperties('WHITEBOARD_URL') . '/preview/' . $room->getUidReal() . '?token=' . $this->whiteboardJwtService->createJwt($room);
            $res = $this->httpClient->request('GET', $url);
            if ($res->getStatusCode() === 200) {
                return '<div class="page_break"></div><img src="data:image/svg+xml;base64,' . base64_encode($res->getContent()) . '"  width="600" />';
            }
        } catch (\Exception $exception) {
        }
        return '';

    }

    public function createEtherpadExport(Rooms $room): string
    {
        try {
            $res = $this->httpClient->request('GET', $this->themeService->getApplicationProperties('ETHERPAD_URL') . '/p/' . $room->getUidReal() . '/export/html');
            if ($res) {
                return '<div class="page_break"></div>' . $res->getContent();
            }
        } catch (\Exception $exception) {
        }
        return '';
    }
}