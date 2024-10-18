<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Star;
use App\Helper\JitsiAdminController;
use App\Service\Star\StarService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class StarController extends JitsiAdminController
{
    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag, private StarService $starService)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }


    #[Route(path: '/star/submit', name: 'app_star', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->starService->createStar(
            $request->get('server'),
            $request->get('star'),
            $request->get('comment'),
            $request->get('browser'),
            $request->get('os')
        );
    }
}
