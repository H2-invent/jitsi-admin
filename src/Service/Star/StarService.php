<?php

namespace App\Service\Star;

use App\Controller\StarController;
use App\Entity\Server;
use App\Entity\Star;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StarService
{
    public function __construct(private LoggerInterface $logger, private EntityManagerInterface $em)
    {
    }

    public function createStar($serverId, $starInt, $comment, $browser, $os): Response
    {
        try {
            $star = new Star();
            $star->setCreatedAt(new \DateTime());
            if ($comment !== '') {
                $star->setComment($comment);
            }
            $this->logger->debug($starInt, ['this ist the star!!!']);
            $star->setStar($starInt);
            if ($os) {
                $star->setOs($os);
            }
            if ($browser) {
                $star->setBrowser($browser);
            }

            $server = $this->em->getRepository(Server::class)->find($serverId);
            if ($server) {
                $star->setServer($server);
                $this->em->persist($star);
                $this->em->flush();
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $res = new JsonResponse(['error' => true]);
            return $res;
        }
        $res = new JsonResponse(['error' => false]);
        $res->headers->set('Access-Control-Allow-Origin:', '*');
        return $res;
    }
}
