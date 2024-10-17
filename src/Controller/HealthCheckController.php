<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController extends JitsiAdminController
{
    #[Route(path: '/health/check', name: 'health_check', methods: ['GET'])]
    public function index(): Response
    {
        try {
            $res = $this->doctrine->getRepository(User::class)->findOneBy([]);
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('Database not working');
        }

        return new JsonResponse(['health' => true]);
    }
    //todo healthcheck mit LDAP erweitern
    //todo DB in ´den HEalthcheck
}
