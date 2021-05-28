<?php

namespace App\Controller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Form\Type\RepeaterType;
use App\Form\Type\RoomType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RepeaterController extends AbstractController
{
    /**
     * @Route("/room/repeater/new", name="repeater_new")
     */
    public function index(Request $request, TranslatorInterface  $translator,): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));
        $repeater = new Repeat();
        $repeater->addRoom($room);
        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_new', ['room' => $room->getId()])]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $repeater = $form->getData();

            }
        }catch (\Exception $exception){
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        return $this->render('repeater/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
