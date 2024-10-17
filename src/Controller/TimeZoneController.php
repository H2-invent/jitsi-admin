<?php

namespace App\Controller;

use App\Form\Type\RoomType;
use App\Form\Type\TimeZoneType;
use App\Helper\JitsiAdminController;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TimeZoneController extends JitsiAdminController
{
    #[Route(path: '/room/timezone/change', name: 'time_zone_change')]
    public function index(Request $request, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(TimeZoneType::class, $user, ['action' => $this->generateUrl('time_zone_save')]);
        return $this->render(
            'time_zone/index.html.twig',
            [
                'form' => $form->createView(),
                'title' => $translator->trans('Zeitzone einstellen')
            ]
        );
    }

    #[Route(path: '/room/timezone/save', name: 'time_zone_save')]
    public function new(Request $request, TranslatorInterface $translator, LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(TimeZoneType::class, $user, ['action' => $this->generateUrl('time_zone_save')]);
        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData();
                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();
            }
        } catch (\Exception $exception) {
            $logger->error($exception->getMessage());
            $this->addFlash('danger', $translator->trans('Fehler'));
            return $this->redirectToRoute('dashboard');
        }
        $this->addFlash('success', $translator->trans('Zeitzone erfolgreich geändert auf: {timeZone}', ['{timeZone}' => $user->getTimeZone()]));
        return $this->redirectToRoute('dashboard');
    }
}
