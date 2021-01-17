<?php

namespace App\Controller;

use App\Entity\ApiKeys;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\RoomService;
use App\Service\UserService;
use PHPUnit\Util\Json;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function GuzzleHttp\default_user_agent;

class APIRoomController extends AbstractController
{
    /**
     * @Route("/api/v1/room", name="api_room_create",methods={"POST"})
     */
    public function index(Request $request, ParameterBagInterface $parameterBag, RoomService $roomService): Response
    {
        $clientApi = $this->getDoctrine()->getRepository(ApiKeys::class)->findOneBy(array('clientSecret' => $request->get('clientSecret')));
        if (!$clientApi) {
            return new JsonResponse(array('error' => true, 'text' => 'No Access'));
        };
        //we are looking for the user
        $email = $request->get('email');
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('email' => $email));
        // if the user does not exist then we make a new one with the Email
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setCreatedAt(new \DateTime());
            $user->setUsername($email);
        }
        //we create the start Datetime
        $start = new \DateTime($request->get('start'));
        $duration = $request->get('duration');
        $name = $request->get('name');
        //we are looking for the server with the Email and the ServerUrl
        $serverUrl = $request->get('server');
        $server = $this->getDoctrine()->getRepository(Server::class)->findServerWithEmailandUrl($serverUrl, $email);
        //If there is no server, then we take the default server which is accessabl for all jitsi admin users
        if (!$server) {
            $server = $this->getDoctrine()->getRepository(Server::class)->find($parameterBag->get('default_jitsi_server_id'));
        }
        // We initialize the Room with the data;
        $room = $roomService->createRoom($user,$server,$start,$duration,$name);
        return new JsonResponse(array('error' => false, 'uid' => $room->getUidReal(),'text'=>'Meeting erfolgreich angelegt'));
    }
    /**
     * @Route("/api/v1/room", name="apiV1_roomDelete", methods={"DELETE"})
     */
    public function removeRoom(Request $request,  ParameterBagInterface $parameterBag, RoomService $roomService): Response
    {
        $clientApi = $this->getDoctrine()->getRepository(ApiKeys::class)->findOneBy(array('clientSecret' => $request->get('clientSecret')));
        if (!$clientApi) {
            return new JsonResponse(array('error' => true, 'text' => 'No Access'));
        };
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));

        if (!$room) {
            return new JsonResponse(array('error' => true, 'text' => 'Room not found '));
        };
        $roomService->deleteRoom($room);
        return new JsonResponse(array('error'=>false,'text'=>'Erfolgreich gelöscht'));
    }
    /**
     * @Route("/api/v1/room", name="api_room_edit",methods={"PUT"})
     */
    public function editRoom(Request $request, ParameterBagInterface $parameterBag, RoomService $roomService): Response
    {
        $clientApi = $this->getDoctrine()->getRepository(ApiKeys::class)->findOneBy(array('clientSecret' => $request->get('clientSecret')));
        if (!$clientApi) {
            return new JsonResponse(array('error' => true, 'text' => 'No Access'));
        };
        $clientApi = $this->getDoctrine()->getRepository(ApiKeys::class)->findOneBy(array('clientSecret' => $request->get('clientSecret')));
        if (!$clientApi) {
            return new JsonResponse(array('error' => true, 'text' => 'No Access'));
        };
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('uidReal' => $request->get('uid')));

        if (!$room) {
            return new JsonResponse(array('error' => true, 'text' => 'Room no found'));
        };

        //we create the start Datetime
        $start = new \DateTime($request->get('start'));
        $duration = $request->get('duration');
        $name = $request->get('name');
        //we are looking for the server with the Email and the ServerUrl
        $serverUrl = $request->get('server');
        $server = $this->getDoctrine()->getRepository(Server::class)->findServerWithEmailandUrl($serverUrl, $room->getModerator()->getEmail());
        //If there is no server, then we take the default server which is accessabl for all jitsi admin users
        if (!$server) {
            $server = $this->getDoctrine()->getRepository(Server::class)->find($parameterBag->get('default_jitsi_server_id'));
        }
        // We initialize the Room with the data;
        $room = $roomService->editRoom($room,$server,$start,$duration,$name);
        return new JsonResponse(array('error' => false, 'uid' => $room->getUidReal(),'text'=>'Meeting erfolgreich geändert'));
    }
}
