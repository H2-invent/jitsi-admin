<?php

namespace App\Controller;


use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController extends AbstractController
{
    /**
     * @Route("/health/check", name="health_check",methods={"GET"})
     */
    public function index(): Response
    {
        try {
            $res = $this->getDoctrine()->getRepository(User::class)->findOneBy(array());
            $user = $res->getFirstname();
        } catch (\Exception $exception) {
            throw $this->createNotFoundException('Database not working');
        }

        return new JsonResponse(array('health' => true));
    }
}
