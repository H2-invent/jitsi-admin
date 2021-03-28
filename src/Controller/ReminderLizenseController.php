<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\Server;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Doctrine\ORM\QueryBuilder;

class ReminderLizenseController extends AbstractController
{
    /**
     * @Route("/reminder/lizense", name="reminder_lizense")
     */
    public function index(MailerService $mailerService, TranslatorInterface $translator): Response
    {
        $counter = 0;
        $back = (new \DateTime())->modify('+5 days');
        $now = new \DateTime();
        $qb = $this->getDoctrine()->getRepository(License::class)->createQueryBuilder('license');
        $qb->andWhere($qb->expr()->gte('license.validUntil', ':now'))
            ->setParameter('now', $now)
            ->andWhere($qb->expr()->lte('license.validUntil', ':back'))
            ->setParameter('back', $back);


        $license = $qb->getQuery()->getResult();
        dump($license);
        $em = $this->getDoctrine()->getManager();
        foreach ($license as $data) {
            $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(array('licenseKey' => $data->getLicenseKey()));
           if($server){
               dump($server);
               $mailerService->sendEmail(
                   $server->getAdministrator()->getEmail(),
                   $translator->trans('Ihre Jitsi-Admin-Enterprise Lizenz läuft bald aus'),
                   $this->renderView('email/licenseReminder.html.twig', array('server' => $server, 'license' => $data)),
                   $server);
           }


        }
        return 0;
        return new JsonResponse(array('error' => false, 'amount' => $counter));
    }
}
