<?php

namespace App\Controller\api;

use App\Entity\Server;
use App\Helper\BearerTokenAuthHelper;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ServerAPIController extends AbstractController
{
    public function __construct(
        private ServerRepository $serverRepository,
        private EntityManagerInterface $entityManager,
        private RoomsRepository $roomsRepository,
        private BearerTokenAuthHelper $bearerTokenAuthHelper,
    )
    {
    }

    #[Route('/api/v1/server/create', name: 'api_server_create',methods: ['POST'])]
    public function index(Request $request): Response
    {
        $apiKey = $this->bearerTokenAuthHelper->getBearerTokenFromRequest($request);
        $server = $this->serverRepository->findOneBy(array('apiKey'=>$apiKey,'isAllowedToCloneForAutoscale'=>true));
        if (!$server) {
            return new JsonResponse(['error' => true, 'text' => 'No Server found. The server mus be allowed to be cloned to autoscale',
            'hint'=>'use the command php bin/console app:server:allowTo #serverid to allow to clone']);
        }
        $serverUSer = $server->getUser()->toArray();

        $newServer = clone $server;
        $newServer->setUrl($request->get('url'))
            ->setServerName($request->get('name'))
            ->setAppId($request->get('app_id'))
            ->setAppSecret($request->get('app_secret'))
            ->setUpdatedAt(new \DateTime())
            ->setAllowedToCloneForAutoscale(null)
            ->setSlug(urlencode($newServer->getUrl()));
        $newServer->getUser()->clear();
        $newServer->setAdministrator(null);
        $this->entityManager->persist($newServer);
        $this->entityManager->flush();

       return new JsonResponse([
           'server_id' => $newServer->getId(),
           'sucess'=>true,
           'error'=>false
       ]);
    }

    #[Route('/api/v1/server/getRooms', name: 'api_server_getRooms',methods: ['GET'])]
    public function getRooms(Request $request): Response
    {
        $apiKey = $this->bearerTokenAuthHelper->getBearerTokenFromRequest($request);
        $server = $this->serverRepository->findOneBy(array('apiKey'=>$apiKey));
        if (!$server) {
            return new JsonResponse(['error' => true, 'text' => 'No Server found']);
        }

        $rooms = $this->roomsRepository->findRoomsForRoomInGivenMinutes($server,$request->get('minutes'));
        $roomIds = array_map(fn($room) => $room->getUidReal(), $rooms);

        return new JsonResponse([
            'server_id' => $server->getId(),
            'success' => true,
            'error' => false,
            'room_ids' => $roomIds
        ]);
    }
}
