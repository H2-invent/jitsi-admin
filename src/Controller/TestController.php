<?php

namespace App\Controller;

use App\Message\LobbyLeaverMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $messageBus
    )
    {
    }

    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        $this->messageBus->dispatch(new LobbyLeaverMessage('123'));

        return new Response('<h1> allet klar nich wahr </h1>');
    }
}
