<?php

namespace App\Controller;

use App\Form\Type\RoomType;
use App\Form\Type\TimeZoneType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TimeZoneController extends AbstractController
{
    /**
     * @Route("/room/timezone/change", name="time_zone_change")
     */
    public function index(Request $request, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(TimeZoneType::class, $user, ['action' => $this->generateUrl('time_zone_save')]);
        return $this->render('time_zone/index.html.twig', array(
            'form' => $form->createView(),
            'title'=> $translator->trans('Zeitzone einstellen')
        ));
    }

    /**
     * @Route("/room/timezone/save", name="time_zone_save")
     */
    public function new(Request $request, TranslatorInterface $translator,LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(TimeZoneType::class, $user, ['action' => $this->generateUrl('time_zone_save')]);
        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData();
                $em = $this->getDOctrine()->getManager();
                $em->persist($user);
                $em->flush();
            }
        }catch (\Exception $exception){
            $logger->error($exception->getMessage());
            return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Fehler'),'color'=>'danger'));
        }
        return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Zeitzone erfolgreich geändert auf: {timeZone}',array('{timeZone}'=>$user->getTimeZone()))));
    }
}
