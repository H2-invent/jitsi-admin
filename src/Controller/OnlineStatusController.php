<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\OnlineStatusService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class OnlineStatusController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry             $managerRegistry,
        TranslatorInterface         $translator,
        LoggerInterface             $logger,
        ParameterBagInterface       $parameterBag,
        private OnlineStatusService $onlineStatusService,)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

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
        return new JsonResponse(['error' => false, 'status' => $this->onlineStatusService->getUserStatus($user)]);
    }
}
