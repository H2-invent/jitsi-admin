<?php

namespace App\Controller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Form\Type\NewMemberType;
use App\Form\Type\RepeaterType;
use App\Form\Type\RoomType;
use App\Helper\JitsiAdminController;
use App\Service\RemoveRoomService;
use App\Service\RepeaterService;
use App\Service\RoomAddService;
use App\Service\ServerUserManagment;
use App\Service\UserService;
use App\UtilsHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RepeaterController extends JitsiAdminController
{
    #[Route(path: '/room/repeater/new', name: 'repeater_new')]
    public function index(ParameterBagInterface $parameterBag, Request $request, RepeaterService $repeaterService): Response
    {


        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('room'));
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            throw new NotFoundHttpException('Not found');
        }
        $repeater = new Repeat();
        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_new', ['room' => $room->getId()])]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $repeater = $form->getData();
                if (!$repeaterService->checkData($repeater)) {
                    $snack = $this->translator->trans('Fehler, Bitte füllen Sie alle Felder aus');
                    $this->addFlash('danger', $snack);
                    return $this->redirectToRoute('dashboard');
                }
                if ($repeater->getRepetation() > $parameterBag->get('laf_max_repeat')) {
                    $snack = $this->translator->trans('Sie dürfen nur maximal {amount} Wiederholungen angeben', ['{amount}' => $parameterBag->get('laf_max_repeat')]);
                    $this->addFlash('danger', $snack);
                    return $this->redirectToRoute('dashboard');
                }
                $em = $this->doctrine->getManager();
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
                $repeaterService->cleanRepeater($repeater);
                $repeater = $repeaterService->createNewRepeater($repeater);
                $repeaterService->addUserRepeat($repeater);
                $repeaterService->sendEMail($repeater, 'email/repeaterNew.html.twig', $this->translator->trans('Eine neue Serienvideokonferenz wurde erstellt'), ['room' => $repeater->getPrototyp()]);
                $snack = $this->translator->trans('Sie haben Erfolgreich einen Serientermin erstellt');
                $this->addFlash('success', $snack);
                return $this->redirectToRoute('dashboard');
            }
        } catch (\Exception $exception) {
            $snack = $this->translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $this->addFlash('danger', $snack);
            return $this->redirectToRoute('dashboard');
        }
        return $this->render(
            'repeater/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    #[Route(path: '/room/repeater/edit/repeat', name: 'repeater_edit_repeater')]
    public function editRepeater(ParameterBagInterface $parameterBag, Request $request, RepeaterService $repeaterService, RoomAddService $roomAddService): Response
    {
        $repeater = $this->doctrine->getRepository(Repeat::class)->find($request->get('repeat'));
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $repeater->getPrototyp())) {
            throw new NotFoundHttpException('Not found');
        }

        $form = $this->createForm(RepeaterType::class, $repeater, ['action' => $this->generateUrl('repeater_edit_repeater', ['repeat' => $repeater->getId()])]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $repeater = $form->getData();
                if (!$repeaterService->checkData($repeater)) {
                    $snack = $this->translator->trans('Fehler, Bitte füllen Sie alle Felder aus');
                    $this->addFlash('danger', $snack);
                    return $this->redirectToRoute('dashboard');
                }
                if ($repeater->getRepetation() > $parameterBag->get('laf_max_repeat')) {
                    $snack = $this->translator->trans('Sie dürfen nur maximal {amount} Wiederholungen angeben', ['{amount}' => $parameterBag->get('laf_max_repeat')]);
                    $this->addFlash('danger', $snack);
                    return $this->redirectToRoute('dashboard');
                }
                $em = $this->doctrine->getManager();
                $em->persist($repeater);
                $em->flush();
                $repeater = $repeaterService->cleanRepeater($repeater);
                $repeater = $repeaterService->createNewRepeater($repeater);
                $repeaterService->addUserRepeat($repeater);
                $repeaterService->sendEMail($repeater, 'email/repeaterEdit.html.twig', $this->translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', ['{name}' => $repeater->getPrototyp()->getName()]), ['room' => $repeater->getPrototyp()]);
                $snack = $this->translator->trans('Sie haben erfolgreich einen Serientermin bearbeitet');
                $this->addFlash('success', $snack);
                return $this->redirectToRoute('dashboard');
            }
        } catch (\Exception $exception) {
            $snack = $this->translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $this->addFlash('danger', $snack);
            return $this->redirectToRoute('dashboard');
        }
        return $this->render(
            'repeater/index.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    #[Route(path: '/room/repeater/remove', name: 'repeater_remove')]
    public function removeRepeater(Request $request, RepeaterService $repeaterService, RemoveRoomService $removeRoomService): Response
    {

        $repeater = $this->doctrine->getRepository(Repeat::class)->find($request->get('repeat'));
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $repeater->getPrototyp())) {
            throw new NotFoundHttpException('Not found');
        }
        $repeaterService->sendEMail(
            $repeater,
            'email/repeaterRemoveUser.html.twig',
            $this->translator->trans(
                'Die Serienvideokonferenz {name} wurde gelöscht',
                ['{name}' => $repeater->getPrototyp()->getName()]
            ),
            ['room' => $repeater->getPrototyp()],
            'CANCEL'
        );

        $em = $this->doctrine->getManager();

        foreach ($repeater->getRooms() as $data) {
            $removeRoomService->deleteRoom($data);
        }

        $repeater->setPrototyp(null);
        $em->persist($repeater);
        $em->flush();
        $snack = $this->translator->trans('Sie haben Erfolgreich einen Serientermin gelöscht');
        $this->addFlash('success', $snack);
        return $this->redirectToRoute('dashboard');
    }

    #[Route(path: '/room/repeater/edit/room', name: 'repeater_edit_room')]
    public function editPrototype(Request $request, RepeaterService $repeaterService, ServerUserManagment $serverUserManagment): Response
    {
        $title = $this->translator->trans('Alle Serienelement der Serie bearbeiten');
        $extra = null;
        $edit = true;
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $room = $this->doctrine->getRepository(Rooms::class)->find($request->get('id'));
        $serverChhose = $room->getServer();
        if ($request->get('type') === 'single') {
            $room->setRepeaterRemoved(true);
            $title = $this->translator->trans('Nur dieses Serienelement bearbeiten');
        } elseif ($request->get('type') === 'all') {
            $extra = $this->translator->trans('repeater.edit.warning');
            $room = $room->getRepeater() !== null ? $room->getRepeater()->getPrototyp() : $room;
        }
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            throw new NotFoundHttpException('Not found');
        }
        $option = [
            'server' => $servers,
            'action' => $this->generateUrl('repeater_edit_room', ['type' => $request->get('type'), 'id' => $room->getId()]),
            'isEdit' => true
        ];
        if ($request->get('type') === 'all') {
            if (new \DateTime() > $room->getStart()) {
                $option['minDate'] = $room->getStart()->format('m/d/Y');
            }
        }
        $form = $this->createForm(RoomType::class, $room, $option);
        $form->remove('scheduleMeeting');
        $form->remove('persistantRoom');
        $form->remove('moderator');
        if (!in_array($room->getServer(), $servers)) {
            $form->remove('server');
        }
        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $this->doctrine->getManager();
                $room = $form->getData();
                if ($room->getRepeaterRemoved()) {//this is a single room. So we take the room out of the series
                    $room->setEnddate((clone $room->getStart())->modify('+' . $room->getDuration() . 'min'));
                    $em->persist($room);
                    $em->flush();
                    $repeater = $room->getRepeater();
                    $repeater->getPrototyp()->setSequence(($repeater->getPrototyp()->getSequence()) + 1);
                    $em->persist($repeater);
                    $em->persist($room);
                    $repeaterService->sendEMail($repeater, 'email/repeaterEdit.html.twig', $this->translator->trans('Die Serienvideokonferenz {name} wurde bearbeitet', ['{name}' => $repeater->getPrototyp()->getName()]), ['room' => $repeater->getPrototyp()]);
                    $snack = $this->translator->trans('Sie haben erfolgreich einen Termin aus einer Terminserie bearbeitet');
                    $res = $this->generateUrl('dashboard', ['snack' => $snack, 'color' => 'success']);
                    return new JsonResponse(['error' => false, 'redirectUrl' => $res]);
                }
                //here we generate a new series. For this we take the old room Prototype and create a new series from it
                $snack = $repeaterService->replaceRooms($room);
                $res = $this->generateUrl('dashboard', ['snack' => $snack, 'color' => 'success']);
                return new JsonResponse(['error' => false, 'redirectUrl' => $res]);
            }
        } catch (\Exception $exception) {
            $snack = $this->translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $this->addFlash('danger', $snack);
            $res = $this->generateUrl('dashboard');
            return new JsonResponse(['error' => false, 'redirectUrl' => $res]);
        }
        return $this->render(
            'base/__newRoomModal.html.twig',
            [
                'serverchoose' => $room->getServer(),
                'form' => $form->createView(),
                'isEdit' => $edit,
                'serverchoose' => $serverChhose,
                'title' => $title,
                'extra' => $extra
            ]
        );
    }
}
