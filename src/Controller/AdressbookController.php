<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\adressbookFavoriteService\AdressbookFavoriteService;
use App\Service\Deputy\DeputyService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdressbookController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry                   $managerRegistry,
        TranslatorInterface               $translator,
        LoggerInterface                   $logger,
        ParameterBagInterface             $parameterBag,
        private AdressbookFavoriteService $adressbookFavoriteService,
        private DeputyService             $deputyService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[Route(path: '/room/adressbook/remove', name: 'adressbook_remove_user')]
    public function index(Request $request): Response
    {
        $user = $this->doctrine->getRepository(User::class)->find($request->get('id'));
        $myUser = $this->getUser();
        $myUser->removeAddressbook($user);
        $this->adressbookFavoriteService->removeFavorite($myUser, $user);
        $this->deputyService->removeDeputy($myUser, $user);
        $em = $this->doctrine->getManager();
        $em->persist($myUser);
        $em->flush();
        return $this->redirectToRoute('dashboard');
    }
}
