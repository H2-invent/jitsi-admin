<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CatchAllController extends AbstractController
{
    /**
     * @Route("/redirect-to-default", name="redirect_to_default")
     */
    public function redirectToDefault(string $catchall): RedirectResponse
    {
        $firstPart = explode('/',$catchall)[0];
        return $this->redirectToRoute('app_public_conference', ['confId' => $firstPart]);
    }
}