<?php

namespace App\Controller\api;

use App\Entity\ApiKeys;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\api\KeycloakService;
use App\Service\api\RoomService;
use App\Service\InviteService;
use App\Service\UserService;
use PHPUnit\Util\Json;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;

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
    public function getRoomInformations(Request $request, $uidReal,RoomService $roomService): Response
    {
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $uidReal));
return new JsonResponse($roomService->generateRoomInfo($room));
    }

    /**
     * @Route("/api/v1/user", name="apiV1_roomAddUser", methods={"POST"})
     */
    public function addUserToRoom(Request $request, InviteService $inviteService, UserService $userService, RoomService  $roomService): Response
    {
        $clientApi = $this->getDoctrine()->getRepository(ApiKeys::class)->findOneBy(array('clientSecret' => $request->get('clientSecret')));
        if (!$clientApi) {
            return new JsonResponse(array('error' => true, 'text' => 'No Access'));
        };
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));
        $email = $request->get('email');
        return new JsonResponse($roomService->addUserToRoom($room,$email));
    }

    /**
     * @Route("/api/v1/user", name="apiV1_roomDeleteUser", methods={"DELETE"})
     */
    public function removeUserFromRoom(Request $request, InviteService $inviteService, RoomService $roomService): Response
    {
        $clientApi = $this->getDoctrine()->getRepository(ApiKeys::class)->findOneBy(array('clientSecret' => $request->get('clientSecret')));
        if (!$clientApi) {
            return new JsonResponse(array('error' => true, 'text' => 'No Access'));
        };
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));
        $email = $request->get('email');
        return new JsonResponse($roomService->removeUserFromRoom($room,$email));
    }
}
