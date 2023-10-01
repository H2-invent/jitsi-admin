<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\JoinMyRoomType;
use App\Form\Type\JoinViewType;
use App\Helper\JitsiAdminController;
use App\Service\RoomService;
use App\Service\StartMeetingService;
use App\UtilsHelper;
use Firebase\JWT\JWT;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class OwnRoomController extends JitsiAdminController
{
    /**
     * @Route("/myRoom/start/{uid}", name="own_room_startPage")
     */
    public function index($uid, Request $request, RoomService $roomService, TranslatorInterface $translator, StartMeetingService $startMeetingService): Response
    {
        $rooms = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uid' => $uid, 'totalOpenRooms' => true]);
        if (!$rooms) {
            $this->addFlash('danger', $translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben'));
            return $this->redirectToRoute('join_index_no_slug');
        }
        if (!StartMeetingService::checkTime($rooms)) {
            $startPrint = $rooms->getTimeZone() ? clone ($rooms->getStartUtc())->setTimeZone(new \DateTimeZone($rooms->getTimeZone())) : $rooms->getStart();
            $startPrint->modify('-30min');
            $endPrint = $rooms->getTimeZone() ? $rooms->getEndDateUtc()->setTimeZone(new \DateTimeZone($rooms->getTimeZone())) : $rooms->getEnddate();
            $snack = $translator->trans(
                'Der Beitritt ist nur von {from} bis {to} möglich',
                [
                    '{from}' => $startPrint->format('d.m.Y H:i T'),
                    '{to}' => $endPrint->format('d.m.Y H:i T')
                ]
            );
            $color = 'danger';
            $this->addFlash($color, $snack);
            return $this->redirectToRoute('join_index_no_slug');
        }

        $data = [];
        if ($this->getUser()) {
            $data['name'] = $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName();
        } elseif ($request->get('name')) {
            $data['name'] = base64_decode($request->get('name'));

        } else {
            if ($request->cookies->get('name')) {
                $data['name'] = $request->cookies->get('name');
            }
        }

        $isModerator = false;
        if (UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $rooms)) {
            $isModerator = true;
        }

        $form = $this->createForm(JoinMyRoomType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $type = 'b';
            if ($form->has('joinApp') && $form->get('joinApp')->isClicked()) {
                $type = 'a';
            } elseif ($form->has('joinBrowser') && $form->get('joinBrowser')->isClicked()) {
                $type = 'b';
            }
            $startMeetingService->setAttribute($rooms, $this->getUser(), $type, $data['name']);

            $url = $roomService->joinUrl($type, $rooms, $data['name'], $isModerator);
            //der Raum ist als dauerhaft markiert
            if (!$rooms->getPersistantRoom()) {
                //Die Lobby ist aktiviert und der Teilnehmer wird direkt in die Lobby überführt.
                // Der teilnehmer muss in der Lobby von einem Lobbymoderator in die Konferenz überführt werden
                if ($rooms->getLobby()) {
                    if ($this->getUser() && UtilsHelper::isAllowedToOrganizeLobby($this->getUser(), $rooms)) {
                        $res = $startMeetingService->createLobbyModeratorResponse();
                    } else {
                        $wui = null;
                        if ($request->cookies->has('waitinguser')) {
                            $wui = $request->cookies->get('waitinguser');
                        }
                        $res = $startMeetingService->createLobbyParticipantResponse($wui);
                        $res->headers->setCookie(new Cookie('waitinguser', $startMeetingService->getLobbyUser()->getUid(), (new \DateTime())->modify('+6 hours')));
                    }
                } else {
                    if ($this->getUser() === $rooms->getModerator()) {
                        $res = $startMeetingService->roomDefault();
                    } else {
                        $res = $this->redirectToRoute('room_waiting', ['name' => $data['name'], 'uid' => $rooms->getUid(), 'type' => $type]);
                    }
                }
            } else {
                //Der Raum hat die Lobby aktiviert
                if ($rooms->getLobby()) {
                    if ($this->getUser() && UtilsHelper::isAllowedToOrganizeLobby($this->getUser(), $rooms)) {
                        $res = $startMeetingService->createLobbyModeratorResponse();
                    } else {
                        $wui = null;
                        if ($request->cookies->has('waitinguser')) {
                            $wui = $request->cookies->get('waitinguser');
                        }
                        $res = $startMeetingService->createLobbyParticipantResponse($wui);
                        $res->headers->setCookie(new Cookie('waitinguser', $startMeetingService->getLobbyUser()->getUid(), (new \DateTime())->modify('+6 hours')));
                    }
                } else {//Der Raum hat keine Lobby Aktiviert -->
                    // Der Fall hier: 1. Keine Zeit angegeben,
                    // 2. es ist keine Lobby aktiviert
                    //Resultat:  also wird der Teilnehmer direkt in die Konferenz überführt. Es wird nichts weiter kontrolliert
                    $res = $startMeetingService->roomDefault();
                }
            }
            $res->headers->setCookie(new Cookie('name', $data['name'], (new \DateTime())->modify('+365 days')));
            return $res;
        }

        return $this->render(
            'own_room/index.html.twig',
            [
                'room' => $rooms,
                'server' => $rooms->getServer(),
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/mywaiting/waiting", name="room_waiting")
     */
    public function waiting(Request $request, StartMeetingService $startMeetingService): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uid' => $request->get('uid')]);
        $name = $request->get('name');
        $type = $request->get('type');
        $now = new \DateTime('now', new \DateTimeZone('utc'));

        if (($room->getStartUtc() < $now && $room->getEndDateUtc() > $now)) {
            $startMeetingService->setAttribute($room, null, $type, $name);
            return $startMeetingService->roomDefault();
        }
        return $this->render(
            'own_room/waiting.html.twig',
            [
                'room' => $room,
                'server' => $room->getServer(),
                'name' => $name,
                'type' => $type
            ]
        );
    }

    /**
     * @Route("/room/enterLink/{uid}", name="room_enter_link")
     */
    public function link(
        #[MapEntity(mapping: ['uid' => 'uid'])]
        Rooms   $rooms,
        Request $request
    ): Response
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $rooms)) {
            throw new NotFoundHttpException('Room not Found');
        }

        return $this->render(
            'own_room/__enterLinkModal.html.twig',
            [
                'room' => $rooms,
            ]
        );
    }

    /**
     * @Route("/mywaiting/check/{uid}/{name}/{type}", name="room_waiting_check")
     */
    public function checkWaiting(
        #[MapEntity(mapping: ['uid' => 'uid'])]
        Rooms $rooms,
              $name, $type,
    ): Response
    {
        $now = new \DateTime('now', new \DateTimeZone('utc'));

        if (($rooms->getStartUtc() < $now && $rooms->getEndDateUtc() > $now)) {
            return new JsonResponse(['error' => false, 'url' => $this->generateUrl('room_waiting', ['name' => $name, 'type' => $type, 'uid' => $rooms->getUid()])]);
        } else {
            return new JsonResponse(['error' => true]);
        }
    }
}
