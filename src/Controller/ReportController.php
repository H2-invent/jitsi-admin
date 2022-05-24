<?php

namespace App\Controller;

use App\Entity\Rooms;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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
     * @ParamConverter("room", class="App\Entity\Rooms")
     */
    public function create(?Rooms $room): Response
    {
        if ($room->getModerator() !== $this->getUser()){
            throw  new NotFoundHttpException('Room not Found');
        }
        $timeZone = $this->getUser()->getTimeZone()?$this->getUser()->getTimeZone():(new \DateTime())->getTimezone()->getName();
        return $this->render('report/index.html.twig', [
            'timezone'=>$timeZone,
            'title' => $this->translator->trans('report.title'),
            'room'=>$room
        ]);
    }
}
