<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\InviteService;
use App\Service\UserService;
use PHPUnit\Util\Json;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class APIUserController extends AbstractController
{
    /**
     * @Route("/api/v1/getAllEntries", name="apiV1_getAllEntries")
     */
    public function index(): Response
    {
        $rooms = $this->getDoctrine()->getRepository(Rooms::class)->findRoomsForUser($this->getUser());
        $res = array();
        foreach ($rooms as $data) {
            $tmp = array(
                'title' => $data->getName(),
                'start' => $data->getStart()->format('Y-m-d') . 'T' . $data->getStart()->format('H:i:s'),
                'end' => $data->getEnddate()->format('Y-m-d') . 'T' . $data->getEnddate()->format('H:i:s'),
                'allDay' => false
            );
            $res[] = $tmp;
        }

        return new JsonResponse($res);
    }

    /**
     * @Route("/api/v1/{uidReal}", name="apiV1_roomGetUser",methods={"GET"})
     */
    public function getUsers(Request $request, $uidReal): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $uidReal));

        if (!$room) {
            return new JsonResponse(array('error' => true, 'text' => 'no Room found'));
        }
        $res = array();
        $user = array();
        foreach ($room->getUser() as $data) {
            $user[] = $data->getEmail();
        }
        $res['teilnehmer'] = $user;
        $res['start'] = $room->getStart()->format('Y-m-dTH:i:s');
        $res['end'] = $room->getEnddate()->format('Y-m-dTH:i:s');
        $res['duration']= $room->getDuration();
        $res['name']= $room->getName();
        $res['moderator'] = $room->getModerator()?$room->getModerator()->getEmail():'';
        $res['server']= $room->getServer()->getUrl();

        return new JsonResponse($res);
    }

    /**
     * @Route("/api/v1/user", name="apiV1_roomAddUser", methods={"POST"})
     */
    public function addUserToRoom(Request $request, InviteService $inviteService, UserService $userService): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));
        $email = $request->get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(array('error' => true, 'text' => 'no Room found'));
        };
        if (!$room) {
            return new JsonResponse(array('error' => true, 'text' => 'Email incorrect'));
        };

        $user = $inviteService->newUser($email);
        $user->addRoom($room);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $userService->addUser($user, $room);
        $em->flush();
        return new JsonResponse(array('uid' => $room->getUidReal(), 'user' => $email, 'error' => false,'text'=>'Teilnehmer '.$email.' erfolgreich hinzugefügt'));
    }

    /**
     * @Route("/api/v1/user", name="apiV1_roomDeleteUser", methods={"DELETE"})
     */
    public function removeUserFromRoom(Request $request, InviteService $inviteService, UserService $userService): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));
        $email = $request->get('email');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('email' => $email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(array('error' => true, 'text' => 'no Room found'));
        };
        if (!$room) {
            return new JsonResponse(array('error' => true, 'text' => 'Email incorrect'));
        };
        if (!$user) {
            return new JsonResponse(array('error' => true, 'text' => 'User incorrect'));
        };

        $room->removeUser($user);
        $em = $this->getDoctrine()->getManager();
        $em->persist($room);
        $em->flush();
        $userService->removeRoom($user, $room);

        return new JsonResponse(array('uid' => $room->getUidReal(), 'user' => $email, 'error' => false,'text'=>'Teilnehmer '.$email.' erfolgreich gelöscht'));
    }
}
