<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Star;
use App\Helper\JitsiAdminController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StarController extends JitsiAdminController
{
    /**
     * @Route("/star/submit", name="app_star", methods={"POST"})
     */
    public function index(Request $request): Response
    {
        try {
            $star = new Star();
            if ($request->get('comment')){
                $star->setComment($request->get('comment'));
            }
            $star->setStar($request->get('star'));
            $server = $this->doctrine->getRepository(Server::class)->find($request->get('server'));
            if ($server){
                $star->setServer($server);
                $em = $this->doctrine->getManager();
                $em->persist($star);
                $em->flush();
            }
        }catch (\Exception $exception){
            return new JsonResponse(array('error'=>true));
        }
      return new JsonResponse(array('error'=>false));
    }
}
