<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function GuzzleHttp\Psr7\str;

class ParticipantController extends AbstractController
{
    /**
     * @Route("/room/participant", name="search_participant")
     */
    public function index(Request $request): Response
    {
       $string = $request->get('search');
       $user = $this->getDoctrine()->getRepository(User::class)->findMyUserByEmail($string,$this->getUser());
       $res = array();
       foreach ($user as $data){
           $res[] = $data->getEmail();
       }
       return new JsonResponse($res);

    }
}
