<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChangelogController extends AbstractController
{
    #[Route('/changelog', name: 'app_changelog')]
    public function index(): Response
    {
        return $this->render(
            'changelog/index.html.twig',
            [
                'controller_name' => 'ChangelogController',
            ]
        );
    }
}
