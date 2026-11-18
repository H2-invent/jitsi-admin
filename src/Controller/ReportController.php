<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Service\Protocol\CreateProtocolService;
use App\UtilsHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\Symfony\Component\Routing\Attribute\Route(path: '/room/report', name: 'app_report')]
class ReportController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}', name: '_create')]
    public function create(
        #[MapEntity(mapping: ['id' => 'id'])]
        ?Rooms $room
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!UtilsHelper::isAllowedToOrganizeRoom($user, $room)) {
            throw new NotFoundHttpException('Room not Found');
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

    #[\Symfony\Component\Routing\Attribute\Route(path: '/{id}/download', name: '_download')]
    public function download(
        #[MapEntity(mapping: ['id' => 'id'])]
        ?Rooms                $room,
        CreateProtocolService $createProtocolService,
    ): Response {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$room || !UtilsHelper::isAllowedToOrganizeRoom($user, $room)) {
            throw new NotFoundHttpException('Room not Found');
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
