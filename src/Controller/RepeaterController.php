<?php

namespace App\Controller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Form\Type\RepeaterType;
use App\Form\Type\RoomType;
use App\Service\RepeaterService;
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
    public function index(Request $request, TranslatorInterface $translator, RepeaterService $repeaterService): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));

        $repeater = new Repeat();
        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_new', ['room' => $room->getId()])]);

//        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $repeater = $form->getData();
                $em = $this->getDoctrine()->getManager();
                foreach ($room->getUser() as $data){
                    $room->addPrototypeUser($data);
                }
                $em->persist($room);
                $em->flush();
                $repeater->setPrototyp($room);
                $repeater->setStartDate($room->getStart());
                $repeaterService->createNewRepeater($repeater);
//                foreach ($room->getUser() as $data){
//                    $room->removeUser($data);
//                }
//                $em->persist($room);
//                $em->flush();
                $snack= $translator->trans('Sie haben Erfolgreich einen Serientermin erstellt');
                return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
            }

//        } catch (\Exception $exception) {
//            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
//            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
//        }
        return $this->render('repeater/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
