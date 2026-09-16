<?php

namespace App\Service\Summary;

use App\Entity\Rooms;
use App\Service\Theme\ThemeService;
use App\Service\Whiteboard\WhiteboardJwtService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class CreateSummaryService
{
    public function __construct(
        private Environment          $environment,
        private HttpClientInterface  $httpClient,
        private ThemeService         $themeService,
        private WhiteboardJwtService $whiteboardJwtService,
        private KernelInterface      $appKernel,
        private LoggerInterface      $logger
    )
    {
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Renders the complete summary document as an HTML string.
     */
    public function createSummary(Rooms $room): string
    {
        return $this->environment->render('documents/summary/template.html.twig', [
            'title' => $room->getName(),
            'header' => $this->createHeader($room),
            'whiteboard' => $this->createWhiteBoardSummary($room),
            'etherpad' => $this->createEtherpadExport($room),
        ]);
    }

    /**
     * Renders the summary as a ready-to-output PDF document.
     */
    public function createSummaryPdf(Rooms $room): ?Dompdf
    {
        $fontDirectory = $this->appKernel->getProjectDir()
            . DIRECTORY_SEPARATOR . 'var'
            . DIRECTORY_SEPARATOR . 'cache';
        $this->logger->debug($fontDirectory);

        $options = new Options();
        $options->set('defaultFont', 'Roboto');
        $options->set('fontDir', $fontDirectory);
        $options->set('fontCache', $fontDirectory);
        $options->set('chroot', $fontDirectory);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->createSummary($room));
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf;
    }

    /**
     * Renders the meeting metadata header (agenda, organiser, schedule, participants).
     */
    public function createHeader(Rooms $room): string
    {
        return $this->environment->render('documents/summary/header.html.twig', ['room' => $room]);
    }

    /**
     * Fetches the whiteboard preview and returns it as an embeddable image block.
     * Returns an empty string when no whiteboard content is available.
     */
    public function createWhiteBoardSummary(Rooms $room): ?string
    {
        try {
            $url = $this->themeService->getApplicationProperties('WHITEBOARD_URL')
                . '/preview/' . $room->getUidReal()
                . '?token=' . $this->whiteboardJwtService->createJwt($room);

            $response = $this->httpClient->request('GET', $url);

            if ($response->getStatusCode() === 200
                && $response->getContent() !== '<text>Sorry, an error occured</text>') {
                return '<div class="page_break"></div><img src="data:image/svg+xml;base64,'
                    . base64_encode($response->getContent())
                    . '" style="width: 600px"/>';
            }
        } catch (\Exception $exception) {
            $this->logger->debug('Whiteboard summary could not be fetched: ' . $exception->getMessage());
        }

        return '';
    }

    /**
     * Fetches the Etherpad HTML export for the room.
     * Returns an empty string when no export is available.
     */
    public function createEtherpadExport(Rooms $room): string
    {
        try {
            $url = $this->themeService->getApplicationProperties('ETHERPAD_URL')
                . '/p/' . $room->getUidReal() . '/export/html';

            $response = $this->httpClient->request('GET', $url);

            if ($response) {
                return '<div class="page_break"></div>' . $response->getContent();
            }
        } catch (\Exception $exception) {
            $this->logger->debug('Etherpad export could not be fetched: ' . $exception->getMessage());
        }

        return '';
    }
}
