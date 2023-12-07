<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class JitsiComponentSelectorPublicKeyController extends AbstractController
{
    #[Route('/jitsi/component/selector/public/key', name: 'app_jitsi_component_selector_public_key')]
    public function index(): Response
    {
        return $this->render('jitsi_component_selector_public_key/index.html.twig', [
            'controller_name' => 'JitsiComponentSelectorPublicKeyController',
        ]);
    }
}
