<?php

namespace App\Controller;

use App\Entity\Server;
use App\Helper\JitsiAdminController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MoreFeaturesController extends JitsiAdminController
{
    /**
     * @Route("/room/features/more", name="more_features",methods={"GET"})
     */
    public function index(Request $request): Response
    {
        $server = $this->doctrine->getRepository(Server::class)->find($request->get('id'));
        return new JsonResponse(array('feature' => array('enableFeateureJwt' => $server->getFeatureEnableByJWT()?true:false)));
    }
}
