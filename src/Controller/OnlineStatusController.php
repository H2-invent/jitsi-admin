<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OnlineStatusController extends JitsiAdminController
{
    public static $ONLINE = 1;
    public static $OFFLINE = 0;
    #[Route('/room/online/status', name: 'app_online_status')]
    public function index(Request $request): Response
    {
        $em = $this->doctrine->getManager();
        $user = $this->getUser();
        $user->setOnlineStatus($request->get('status'));
        $em->persist($user);
        $em->flush();
        return new JsonResponse(array('error' => false, 'status' => $user->getOnlineStatus()));
    }
}
