<?php

namespace App\Controller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Form\Type\NewMemberType;
use App\Form\Type\RepeaterType;
use App\Form\Type\RoomType;
use App\Service\RepeaterService;
use App\Service\RoomAddService;
use App\Service\ServerUserManagment;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RepeaterController extends AbstractController
{
    /**
     * @Route("/room/repeater/new", name="repeater_new")
     */
    public function index(ParameterBagInterface $parameterBag, RoomAddService $roomAddService, Request $request, TranslatorInterface $translator, RepeaterService $repeaterService): Response
    {


        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));
        if ($room->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Not found');
        }
        $repeater = new Repeat();
        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_new', ['room' => $room->getId()])]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $repeater = $form->getData();
                if (!$repeaterService->checkData($repeater)) {
                    $snack = $translator->trans('Fehler, Bitte füllen Sie alle Felder aus');
                    return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
                }
                if ($repeater->getRepetation() > $parameterBag->get('laf_max_repeat')) {
                    $snack = $translator->trans('Sie dürfen nur maximal {amount} Wiederholungen angeben', array('{amount}' => $parameterBag->get('laf_max_repeat')));
                    return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
                }
                $em = $this->getDoctrine()->getManager();
                foreach ($room->getUser() as $data) {
                    $room->addPrototypeUser($data);
                    $room->removeUser($data);
                }
                $room->setPublic(false);
                $em->persist($room);
                $em->flush();

                $repeater->setPrototyp($room);
                $repeater->setStartDate($room->getStart());
                $em->persist($repeater);
                $em->flush();
                $userAttributes = $repeater->getPrototyp()->getUserAttributes()->toArray();
                $repeater = $repeaterService->createNewRepeater($repeater);
                $repeaterService->addUserRepeat($repeater);
                $repeaterService->sendEMail($repeater, 'email/repeaterNew.html.twig', $translator->trans('Eine neue Serienvideokonferenz wurde erstellt'), array('room' => $repeater->getPrototyp()));
                $snack = $translator->trans('Sie haben Erfolgreich einen Serientermin erstellt');
                return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
            }

        } catch (\Exception $exception) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        return $this->render('repeater/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/room/repeater/edit/repeat", name="repeater_edit_repeater")
     */
    public function editRepeater(ParameterBagInterface $parameterBag, Request $request, TranslatorInterface $translator, RepeaterService $repeaterService, RoomAddService $roomAddService): Response
    {
        //todo check if allowed
        $repeater = $this->getDoctrine()->getRepository(Repeat::class)->find($request->get('repeat'));
        if ($repeater->getPrototyp()->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Not found');
        }

        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_edit_repeater', ['repeat' => $repeater->getId()])]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $repeater = $form->getData();
                if (!$repeaterService->checkData($repeater)) {
                    $snack = $translator->trans('Fehler, Bitte füllen Sie alle Felder aus');
                    return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
                }
                if ($repeater->getRepetation() > $parameterBag->get('laf_max_repeat')) {
                    $snack = $translator->trans('Sie dürfen nur maximal {amount} Wiederholungen angeben', array('{amount}' => $parameterBag->get('laf_max_repeat')));
                    return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
                }
                $em = $this->getDoctrine()->getManager();
                foreach ($repeater->getRooms() as $data) {
                    foreach ($data->getUser() as $data2) {
                        $data2->removeRoom($data);
                        $em->persist($data2);
                    }
                    foreach ($data->getUserAttributes() as $data2) {
                        $em->remove($data2);
                        $data->removeUserAttribute($data2);
                    }
                    $em->remove($data);
                    $repeater->removeRoom($data);
                }
                $repeater->getPrototyp()->setSequence(($repeater->getPrototyp()->getSequence()) + 1);
                $em->persist($repeater);
                $em->flush();
                $repeater = $repeaterService->createNewRepeater($repeater);
                $repeaterService->addUserRepeat($repeater);
                $repeaterService->sendEMail($repeater, 'email/repeaterEdit.html.twig', $translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', array('{name}' => $repeater->getPrototyp()->getName())), array('room' => $repeater->getPrototyp()));
                $snack = $translator->trans('Sie haben erfolgreich einen Serientermin bearbeitet');
                return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
            }

        } catch (\Exception $exception) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        return $this->render('repeater/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/room/repeater/remove", name="repeater_remove")
     */
    public function removeRepeater(Request $request, TranslatorInterface $translator, RepeaterService $repeaterService): Response
    {

        $repeater = $this->getDoctrine()->getRepository(Repeat::class)->find($request->get('repeat'));
        if ($repeater->getPrototyp()->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Not found');
        }
        $repeaterService->sendEMail(
            $repeater,
            'email/repeaterRemoveUser.html.twig',
            $translator->trans('Die Serienvideokonferenz {name} wurde gelöscht',
                array('{name}' => $repeater->getPrototyp()->getName())),
            array('room' => $repeater->getPrototyp()),
            'CANCEL');

        $em = $this->getDoctrine()->getManager();
        foreach ($repeater->getRooms() as $data) {
            foreach ($data->getUser() as $data2) {
                $data->removeUser($data2);
                $em->persist($data);
            }
        }

        $repeater->setPrototyp(null);
        $em->persist($repeater);
        $em->flush();
        $snack = $translator->trans('Sie haben Erfolgreich einen Serientermin gelöscht');
        return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'success'));
    }

    /**
     * @Route("/room/repeater/edit/room", name="repeater_edit_room")
     */
    public function editPrototype(RoomAddService $roomAddService, Request $request, UserService $userService, TranslatorInterface $translator, RepeaterService $repeaterService, ServerUserManagment $serverUserManagment): Response
    {
        $title = $translator->trans('Alle Serienelement der Serie bearbeiten');
        $extra = null;
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $room = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('id'));
        if($request->get('type') === 'single'){
         $room->setRepeaterRemoved(true);
         $title = $translator->trans('Nur dieses Serienelement bearbeiten');
        }elseif ($request->get('type') === 'all'){
            $extra = $translator->trans('Das Datum wird nicht berücksichtigt, da dieses bereits durch die Serie festgelegt ist');
        }
        if ($room->getModerator() !== $this->getUser()) {
            throw new NotFoundHttpException('Not found');
        }
        $form = $this->createForm(RoomType::class, $room, ['server' => $servers, 'action' => $this->generateUrl('repeater_edit_room', ['type'=>$request->get('type'),'id' => $room->getId()])]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $room = $form->getData();
                if ($room->getRepeaterRemoved()) {
                    $room->setEnddate((clone $room->getStart())->modify('+'.$room->getDuration().'min'));
                    $em->persist($room);
                    $em->flush();
                    $repeater = $room->getRepeater();
                    $repeater->getPrototyp()->setSequence(($repeater->getPrototyp()->getSequence()) + 1);
                    $em->persist($repeater);
                    $em->persist($room);
                    $repeaterService->sendEMail($repeater, 'email/repeaterEdit.html.twig', $translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', array('{name}' => $repeater->getPrototyp()->getName())), array('room' => $repeater->getPrototyp()));
                    $snack = $translator->trans('Sie haben erfolgreich einen Termin aus einer Terminserie bearbeitet');
                    $res = $this->generateUrl('dashboard', ['snack' => $snack, 'color' => 'success']);
                    return new JsonResponse(array('error'=>false, 'redirectUrl'=>$res));
                }

                $repeater = $repeaterService->replaceRooms($room);
                $repeaterService->sendEMail($repeater, 'email/repeaterEdit.html.twig', $translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', array('{name}' => $repeater->getPrototyp()->getName())), array('room' => $repeater->getPrototyp()));

                $snack = $translator->trans('Sie haben erfolgreich einen Serientermin bearbeitet');
                $res = $this->generateUrl('dashboard', ['snack' => $snack, 'color' => 'success']);
                return new JsonResponse(array('error'=>false, 'redirectUrl'=>$res));
            }

        } catch (\Exception $exception) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $res = $this->generateUrl('dashboard', array('snack' => $snack, 'color' => 'danger'));

            return new JsonResponse(array('error'=>false,'redirectUrl'=>$res));
        }
        return $this->render('base/__newRoomModal.html.twig', [
            'form' => $form->createView(),
            'title' => $title,
            'extra'=>$extra
        ]);
    }

}
