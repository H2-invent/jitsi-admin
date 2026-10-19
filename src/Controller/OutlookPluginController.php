<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\User;
use App\Service\CreateHttpsUrl;
use App\Service\PublicConference\PublicConferenceService;
use App\Service\ServerUserManagment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/room/outlook/plugin', name: 'app_outlook_plugin')]
final class OutlookPluginController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServerUserManagment $serverUserManagment,
        private readonly PublicConferenceService $publicConferenceService,
        private readonly  CreateHttpsUrl $createHttpsUrl
    )
    {

    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        /**@var \App\Entity\User $user * */
        $user = $this->getUser();
        if (!$user->getOutlookPluginToken()) {
            $user->setOutlookPluginToken(md5(uniqid($user->getUid(), true)));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        return new JsonResponse(['token' => $user->getOutlookPluginToken()]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        /**@var \App\Entity\User $user * */
        $user = $this->getUser();
        if ($user->getOutlookPluginToken()) {
            $user->setOutlookPluginToken(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        return new JsonResponse(['token' => $user->getOutlookPluginToken()]);
    }

    #[Route('/server', name: 'server')]
    public function server(Request $request): Response
    {
        $token = $request->headers->get('Authorization');
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['outlookPluginToken' => $token]);
        if ($user){
           $server = $this->serverUserManagment->getServersFromUser($user);
           $serverArr = [];
           foreach($server as $data){
               $serverArr[] = ['id'=>$data->getId(),'name'=>$data->getServerName()];
           }
           return new JsonResponse($serverArr);
        }else{
            return new JsonResponse(['error'=>true,'reason'=>'User not found']);
        }
    }

    #[Route('/createConference', name: 'createConference')]
    public function createConference(Request $request): Response
    {
        $serverID = $request->get('server_id');
        $server = $this->entityManager->getRepository(Server::class)->find($serverID);
        if (!$server){
            return new JsonResponse(['error'=>true,'reason'=>'Server not found']);

        }
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['outlookPluginToken'=>$request->headers->get('token')]);
        if (!$user){
            return new JsonResponse(['error'=>true,'reason'=>'User not found']);
        }
        $serverAvailable = $this->serverUserManagment->getServersFromUser($user);
        if (!in_array($server,$serverAvailable)){
            return new JsonResponse(['error'=>true,'reason'=>'Server not allowed for user']);
        }
        $room = $this->publicConferenceService->createNewRoomFromName(md5(uniqid()), $server);
        $room
            ->setModerator($this->getUser())
            ->setPublic(true)
            ->setTotalOpenRooms(true)
            ->setIsFastConference(true)
        ;
        $this->entityManager->persist($room);
        $this->entityManager->flush();
        return new JsonResponse(
            [
                ['url' => $this->createHttpsUrl->createHttpsUrl($this->generateUrl('room_join', ['t' => 'b', 'room' => $room->getId()])), 'title' => $room->getName()]
            ]
        );
    }
}
