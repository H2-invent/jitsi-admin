<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use function Doctrine\ORM\QueryBuilder;

class ReminderLizenseController extends AbstractController
{
    /**
     * @Route("/reminder/lizense", name="reminder_lizense")
     */
    public function index(): Response
    {
        $counter = 0;
        $now = new \DateTime();
        $qb = $this->getDoctrine()->getRepository(License::class)->createQueryBuilder('license');
        $qb->andWhere('license.validUntil < :now')
            ->setParameter('now', $now)
            ->andWhere($qb->expr()->isNull('license.reminded'));

        $license = $qb->getQuery()->getResult();

        foreach ($license as $data){
            $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(array('licenseKey'=>$data->getLicenseKey()));


        }

        return new JsonResponse(array('error' => false, 'amount' => $counter));
    }
}
