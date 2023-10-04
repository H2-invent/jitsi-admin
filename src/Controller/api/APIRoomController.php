<?php

namespace App\Controller\api;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Helper\JitsiAdminController;
use App\Service\api\KeycloakService;
use App\Service\api\RoomService;
use App\Service\LicenseService;
use App\Service\ServerUserManagment;
use App\Service\UserCreatorService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class APIRoomController extends JitsiAdminController
{
    /**
     * @Route("/api/v1/room", name="api_room_create",methods={"POST"})
     */
    public function index(UserCreatorService $userCreatorService, LicenseService $licenseService, Request $request, ParameterBagInterface $parameterBag, RoomService $roomService, KeycloakService $keycloakService): Response
    {

        //we are looking for the user
        $email = $request->get('email');

        $user = $keycloakService->getUSer($email, $request->get('keycloakId'));
        // if the user does not exist then we make a new one with the Email
        if (!$user) {
            $user = $userCreatorService->createUser($email, $email, '', '');
        }
        $serverUrl = $request->get('server');
        $apiKey = $request->headers->get('Authorization');
        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
        $server = $this->doctrine->getRepository(Server::class)->findServerWithEmailandUrl($serverUrl, $email, $apiKey);
        if (!$server) {
            return new JsonResponse(['error' => true, 'text' => 'No Server found']);
        }
        //we create the start Datetime
        $start = new \DateTime($request->get('start'));
        $duration = $request->get('duration');
        $name = $request->get('name');
        //we are looking for the server with the Email and the ServerUrl

        //If there is no server, then we take the default server which is accessabl for all jitsi admin users

        // We initialize the Room with the data;
        try {
            $room = $roomService->createRoom($user, $server, $start, $duration, $name);
        } catch (\Exception $exception) {
            return new JsonResponse(['error' => true]);
        }

        return new JsonResponse(['error' => false, 'uid' => $room->getUidReal(), 'text' => 'Meeting erfolgreich angelegt']);
    }

    /**
     * @Route("/api/v1/room", name="apiV1_roomDelete", methods={"DELETE"})
     */
    public function removeRoom(Request $request, ParameterBagInterface $parameterBag, RoomService $roomService): Response
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $request->get('uid')]);

        if (!$room || $room->getModerator() === null) {
            return new JsonResponse(['error' => true, 'text' => 'Room not found ']);
        };
        $apiKey = $request->headers->get('Authorization');
        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
        if ($room->getServer()->getApiKey() !== $apiKey) {
            return new JsonResponse(['error' => true, 'text' => 'No Server found']);
        }
        $roomService->deleteRoom($room);
        return new JsonResponse(['error' => false, 'text' => 'Erfolgreich gelöscht']);
    }

    /**
     * @Route("/api/v1/room", name="api_room_edit",methods={"PUT"})
     */
    public function editRoom(LicenseService  $licenseService, Request $request, ParameterBagInterface $parameterBag, RoomService $roomService): Response
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $request->get('uid')]);

        if (!$room || $room->getModerator() === null) {
            return new JsonResponse(['error' => true, 'text' => 'Room no found']);
        };

        //we create the start Datetime
        $start = new \DateTime($request->get('start'));
        $duration = $request->get('duration');
        $name = $request->get('name');
        //we are looking for the server with the Email and the ServerUrl
        $serverUrl = $request->get('server');
        $apiKey = $request->headers->get('Authorization');
        // skip beyond "Bearer "
        $apiKey = substr($apiKey, 7);
        $server = $this->doctrine->getRepository(Server::class)->findServerWithEmailandUrl($serverUrl, $room->getModerator()->getEmail(), $apiKey);
        //If there is no server, then we take the default server which is accessabl for all jitsi admin users
        if (!$server) {
            return new JsonResponse(['error' => true, 'text' => 'No Server found']);
        }
        // We initialize the Room with the data;
        $room = $roomService->editRoom($room, $server, $start, $duration, $name);
        return new JsonResponse(['error' => false, 'uid' => $room->getUidReal(), 'text' => 'Meeting erfolgreich geändert']);
    }

    /**
     * @Route("/api/v1/serverInfo", name="api_user_get_server",methods={"GET"})
     */
    public function getServers(ServerUserManagment  $serverUserManagment, Request $request, ParameterBagInterface $parameterBag, RoomService $roomService, KeycloakService $keycloakService): Response
    {

        $user = $keycloakService->getUSer($request->get('email'), $request->get('keycloakId'));
        $server = $serverUserManagment->getServersFromUser($user);

        $serv = [];
        $res = [];
        foreach ($server as $data) {
            $serv[] = $data->getUrl();
        }
        $res['server'] = $serv;
        $res['email'] = $user->getEmail();
        $res['error'] = false;
        return new JsonResponse($res);
    }
}
