<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\Server;
use App\Helper\JitsiAdminController;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReminderLizenseController extends JitsiAdminController
{
    #[Route(path: '/reminder/lizense', name: 'reminder_lizense')]
    public function index(LoggerInterface $logger, Request $request, MailerService $mailerService, ParameterBagInterface $parameterBag): Response
    {
        if ($request->get('token') !== $parameterBag->get('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $logger->error($message['hinweis'], $message);
            return new JsonResponse($message);
        }
        $counter = 0;
        $back = (new \DateTime())->modify('+5 days');
        $now = new \DateTime();
        $qb = $this->doctrine->getRepository(License::class)->createQueryBuilder('license');
        $qb->andWhere($qb->expr()->gte('license.validUntil', ':now'))
            ->setParameter('now', $now)
            ->andWhere($qb->expr()->lte('license.validUntil', ':back'))
            ->setParameter('back', $back);
        $license = $qb->getQuery()->getResult();

        $error = false;
        $message = '';
        try {
            foreach ($license as $data) {
                $server = $this->doctrine->getRepository(Server::class)->findOneBy(['licenseKey' => $data->getLicenseKey()]);
                if ($server) {
                    $mailerService->sendEmail(
                        $server->getAdministrator(),
                        $this->translator->trans('Ihre Jitsi-Admin-Enterprise Lizenz läuft bald aus'),
                        $this->renderView('email/licenseReminder.html.twig', ['server' => $server, 'license' => $data]),
                        $server
                    );
                    $counter++;
                }
            }
        } catch (\Exception $e) {
            $error = true;
            $message = $e->getMessage();
        }

        return new JsonResponse(['error' => $error, 'message' => $message, 'amount' => $counter]);
    }
}
