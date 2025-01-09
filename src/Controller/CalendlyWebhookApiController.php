<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CalendlyWebhookApiController extends AbstractController
{
    #[Route('/calendly/webhook/api', name: 'app_calendly_webhook_api')]
    public function index(): Response
    {
        return $this->render('calendly_webhook_api/index.html.twig', [
            'controller_name' => 'CalendlyWebhookApiController',
        ]);
    }
}
