<?php

/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 15.05.2020
 * Time: 09:15
 */

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\JoinViewType;
use App\Helper\JitsiAdminController;
use App\Service\ThemeService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DashboardController
 * @package App\Controller
 */
class IndexController extends JitsiAdminController
{
    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag, private ThemeService $themeService)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/', name: 'index')]
    public function index(Request $request)
    {

        if ($this->getUser() || $this->themeService->getApplicationProperties('laF_startpage') == 0) {
            if ($this->getUser()) {
                return $this->redirectToRoute('dashboard');
            };

            return $this->redirectToRoute('app_public_form');
        };

        $data = [];
        // dataStr wird mit den Daten uid und email encoded übertragen. Diese werden daraufhin als Vorgaben in das Formular eingebaut
        $dataStr = $request->get('data');
        $dataAll = base64_decode($dataStr);
        parse_str($dataAll, $data);
        $form = $this->createForm(JoinViewType::class, $data, ['action' => $this->generateUrl('join_index')]);
        $form->handleRequest($request);
        $user = $this->doctrine->getRepository(User::class)->findAll();
        $server = $this->doctrine->getRepository(Server::class)->findAll();
        $rooms = $this->doctrine->getRepository(Rooms::class)->findAll();
        return $this->render('dashboard/start.html.twig', ['form' => $form->createView(), 'user' => $user, 'server' => $server, 'rooms' => $rooms]);
    }
}
