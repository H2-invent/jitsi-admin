<?php

namespace App\Controller;


use App\Entity\User;
use App\Helper\JitsiAdminController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController extends JitsiAdminController
{
    /**
     * @Route("/health/check", name="health_check",methods={"GET"})
     */
    public function index(): Response
    {
        try {
            $res = $this->doctrine->getRepository(User::class)->findOneBy(array());
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('Database not working');
        }

        return new JsonResponse(array('health' => true));
    }
}
