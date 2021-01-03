<?php

namespace App\Controller;


use App\Entity\Rooms;
use App\Service\AddUserService;
use App\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends AbstractController
{
    /**
     * @Route("/cron/remember", name="cron_remember")
     */
    public function updateCronAkademie(NotificationService $notificationService, Request $request, LoggerInterface $logger, AddUserService $addUserService)
    {
        if ($request->get('token') !== $this->getParameter('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $logger->error($message['hinweis'], $message);
            return new JsonResponse($message);
        }
        $now10 = new \DateTime();
        $now10->modify('+ 10 minutes');

        $qb = $this->getDoctrine()->getRepository(Rooms::class)->createQueryBuilder('rooms');
        $qb->where('rooms.start > :now')
            ->andWhere('rooms.start < :now10')
            ->setParameter('now10', $now10)
            ->setParameter('now', new \DateTime());
        $query = $qb->getQuery();
        $rooms = $query->getResult();
        $emails = 0;
        foreach ($rooms as $room) {
            foreach ($room->getUser() as $data) {
                $addUserService->notifyUser($data,$room);
                ++ $emails;
            }
        }

        $message = ['error' => false, 'hinweis' => 'Cron ok', 'Konferenzen'=>count($rooms), 'Emails' => $emails];
        return new JsonResponse($message);
    }
}
