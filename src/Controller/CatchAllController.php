<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class CatchAllController extends AbstractController
{
    #[Route(path: '/redirect-to-default', name: 'redirect_to_default')]
    public function redirectToDefault(string $catchall): RedirectResponse
    {
        $firstPart = explode('/', $catchall)[0];
        return $this->redirectToRoute('app_public_conference', ['confId' => $firstPart]);
    }
}