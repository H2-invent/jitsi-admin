<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Star;
use App\Helper\JitsiAdminController;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class StarController extends JitsiAdminController
{


    /**
     * @Route("/star/submit", name="app_star", methods={"GET"})
     */
    public function index(Request $request): Response
    {
        try {
            $star = new Star();
            $star->setCreatedAt(new \DateTime());
            if ($request->get('comment')) {
                $star->setComment($request->get('comment'));
            }
            $this->logger->debug($request->get('star'), array('this ist the star!!!'));
            $star->setStar($request->get('star'));
            $server = $this->doctrine->getRepository(Server::class)->find($request->get('server'));
            if ($server) {
                $star->setServer($server);
                $em = $this->doctrine->getManager();
                $em->persist($star);
                $em->flush();
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $res = new JsonResponse(array('error' => true));


        }
        $res = new JsonResponse(array('error' => false));
        $res->headers->set('Access-Control-Allow-Origin:', '*');
        return $res;
    }
}
