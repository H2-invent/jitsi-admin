<?php

namespace App\Service;

use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use function Doctrine\ORM\QueryBuilder;

class ReminderService
{
    private $em;
    private $parameterBag;
    private $userService;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag, UserService $userService)
    {
        $this->em = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->userService = $userService;
    }

    public function sendReminder($filter)
    {
        set_time_limit(600);
        $now = (new \DateTime())->setTimezone(new \DateTimeZone('utc'));
        $now10 = (clone $now)->modify('+ 10 minutes');

        $qb = $this->em->getRepository(Rooms::class)->createQueryBuilder('rooms');
        $qb->where('rooms.startUtc > :now')
            ->andWhere('rooms.startUtc < :now10')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('rooms.scheduleMeeting', ':false'),
                    $qb->expr()->isNull('rooms.scheduleMeeting')
                )
            )
            ->setParameter('now10', $now10)
            ->setParameter('now', $now)
            ->setParameter(':false', false);

        if ($filter) {
            $orX = $qb->expr()->orX();
            $count = 0;
            foreach ($filter as $data) {
                if ($data === null) {
                    $orX->add($qb->expr()->isNull('rooms.hostUrl'));
                } else {
                    $orX->add($qb->expr()->eq('rooms.hostUrl', ':url' . $count));
                    $qb->setParameter(':url' . $count++, $data);
                }
            }
            $qb->andWhere($orX);
        }

        $query = $qb->getQuery();
        $rooms = $query->getResult();
        $emails = 0;
        foreach ($rooms as $room) {
            foreach ($room->getUser() as $data) {
                $this->userService->notifyUser($data, $room);
                ++$emails;
            }
        }
        $message = ['error' => false, 'hinweis' => 'Cron ok', 'Konferenzen' => count($rooms), 'Emails' => $emails];
        return $message;
    }
}
