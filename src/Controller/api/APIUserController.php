<?php

namespace App\Controller\api;

use App\Entity\ApiKeys;
use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\api\KeycloakService;
use App\Service\api\RoomService;
use App\Service\InviteService;
use App\Service\LicenseService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Util\Json;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;

class APIUserController extends JitsiAdminController
{

    /**
     * @Route("/api/v1/getAllEntries", name="apiV1_getAllEntries")
     */
    public function index(): Response
    {
        $rooms = $this->doctrine->getRepository(Rooms::class)->findRoomsForUser($this->getUser());
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
        $response = new JsonResponse($res);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * @Route("/api/v1/info/{uidReal}", name="apiV1_roomGetUser",methods={"GET"})
     */
    public function getRoomInformations(Request $request, $uidReal, RoomService $roomService): Response
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $uidReal));
        $response = new JsonResponse($roomService->generateRoomInfo($room));
        $response->headers->set('Access-Control-Allow-Origin', '*');
        return $response;
    }

    /**
     * @Route("/api/v1/user", name="apiV1_roomAddUser", methods={"POST"})
     */
    public function addUserToRoom(LicenseService $licenseService, Request $request, InviteService $inviteService, UserService $userService, RoomService $roomService): Response
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));
        $apiKey = $request->headers->get('Authorization');
        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
         if ($room->getServer()->getApiKey() !== $apiKey ||  !$licenseService->verify($room->getServer()) ) {
            return new JsonResponse(array('error' => true, 'text' => 'No Server found'));
        }
        $email = $request->get('email');
        return new JsonResponse($roomService->addUserToRoom($room, $email));
    }

    /**
     * @Route("/api/v1/user", name="apiV1_roomDeleteUser", methods={"DELETE"})
     */
    public function removeUserFromRoom(LicenseService $licenseService, Request $request, InviteService $inviteService, RoomService $roomService): Response
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));
        $apiKey = $request->headers->get('Authorization');
        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
        if ($room->getServer()->getApiKey() !== $apiKey ||  !$licenseService->verify($room->getServer()) ) {
            return new JsonResponse(array('error' => true, 'text' => 'No Server found'));
        }
        $email = $request->get('email');
        return new JsonResponse($roomService->removeUserFromRoom($room, $email));
    }
}
