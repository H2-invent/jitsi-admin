<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class APIController extends AbstractController
{
    /**
     * @Route("/api/v1/getAllEntries", name="apiV1_getAllEntries")
     */
    public function index(): Response
    {
        $rooms = $this->getDoctrine()->getRepository(Rooms::class)->findRoomsForUser( $this->getUser());
        $res = array();
        foreach ($rooms as $data) {
            $tmp = array(
                'title' => $data->getName(),
                'start'=>$data->getStart()->format('Y-m-d').'T'.$data->getStart()->format('H:i:s'),
                'end'=>$data->getEnddate()->format('Y-m-d').'T'.$data->getEnddate()->format('H:i:s'),
                'allDay'=> false
            );
            $res[] = $tmp;
        }

        return new JsonResponse($res);
    }
}
