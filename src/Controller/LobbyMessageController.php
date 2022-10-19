<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LobbyMessageController extends AbstractController
{
    #[Route('/lobby/message', name: 'app_lobby_message')]
    public function index(): Response
    {
        return $this->render('lobby_message/index.html.twig', [
            'controller_name' => 'LobbyMessageController',
        ]);
    }
}
