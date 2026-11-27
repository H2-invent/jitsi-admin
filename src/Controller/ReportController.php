<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Protocol\CreateProtocolService;
use App\UtilsHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/room/report', name: 'app_report')]
class ReportController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[Route(path: '/{id}', name: '_create')]
    public function create(
        #[MapEntity(mapping: ['id' => 'id'])]
        ?Rooms $room
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!UtilsHelper::isAllowedToOrganizeRoom($user, $room)) {
            throw $this->createAccessDeniedException('Only moderators can access the report');
        }

        $timeZone = $user->getTimeZone() ?: new \DateTimeImmutable()->getTimezone()->getName();

        return $this->render(
            'report/index.html.twig',
            [
                'timezone' => $timeZone,
                'title'    => $this->translator->trans('report.title'),
                'room'     => $room
            ]
        );
    }

    #[Route(path: '/{id}/download', name: '_download')]
    public function download(
        #[MapEntity(mapping: ['id' => 'id'])]
        ?Rooms                $room,
        CreateProtocolService $createProtocolService,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$room || !UtilsHelper::isAllowedToOrganizeRoom($user, $room)) {
            throw $this->createAccessDeniedException('Only moderators can access the report');
        }

        $pdf      = $createProtocolService->createProtocolPdf($room, $user);
        $fileName = new AsciiSlugger()->slug(
            $this->translator->trans('protocol.pdf.filename') . '-' . $room->getName()
        )->toString() . '.pdf';

        return new Response(
            $pdf->output(),
            Response::HTTP_OK,
            [
                'Cache-Control'       => 'private',
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }
}
