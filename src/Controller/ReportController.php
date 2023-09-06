<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\UtilsHelper;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/room/report", name="app_report")
 */
class ReportController extends AbstractController
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/{id}", name="_create")
     */
    public function create(
        #[MapEntity(mapping: ['id' => 'id'])]
        ?Rooms $room
    ): Response
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            throw  new NotFoundHttpException('Room not Found');
        }
        $timeZone = $this->getUser()->getTimeZone() ? $this->getUser()->getTimeZone() : (new \DateTime())->getTimezone()->getName();
        return $this->render(
            'report/index.html.twig',
            [
                'timezone' => $timeZone,
                'title' => $this->translator->trans('report.title'),
                'room' => $room
            ]
        );
    }
}
