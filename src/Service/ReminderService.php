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
    public function __construct(EntityManagerInterface  $entityManager, ParameterBagInterface $parameterBag,UserService  $userService)
    {
        $this->em = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->userService = $userService;
    }

    public function sendReminder(){
        set_time_limit(600);
        $now10 = new \DateTime();
        $now10->modify('+ 10 minutes');

        $qb = $this->em->getRepository(Rooms::class)->createQueryBuilder('rooms');
        $qb->where('rooms.start > :now')
            ->andWhere('rooms.start < :now10')
            ->andWhere('rooms.scheduleMeeting != true')
            ->setParameter('now10', $now10)
            ->setParameter('now', new \DateTime());
        $query = $qb->getQuery();
        $rooms = $query->getResult();
        $emails = 0;
        foreach ($rooms as $room) {
            foreach ($room->getUser() as $data) {
                $this->userService->notifyUser($data,$room);
                ++ $emails;
            }
        }
        $message = ['error' => false, 'hinweis' => 'Cron ok', 'Konferenzen'=>count($rooms), 'Emails' => $emails];
        return $message;
    }

}