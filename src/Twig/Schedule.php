<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\SchedulingTimeUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Schedule extends AbstractExtension
{
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('scheduleNumber', [$this, 'scheduleNumber']),
            new TwigFunction('scheduleUser', [$this, 'scheduleUser']),
            new TwigFunction('scheduleOwnJoice', [$this, 'scheduleOwnJoice']),
            new TwigFunction('scheduleUserHasVoted', [$this, 'scheduleUserHasVoted']),
            new TwigFunction('myScheduledMeeting', [$this, 'myScheduledMeeting']),
        ];
    }

    public function scheduleOwnJoice(User $user, SchedulingTime $schedulingTime): ?int
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findOneBy(['user' => $user, 'scheduleTime' => $schedulingTime]);
        if (!$scheduleTimeUser) {
            return null;
        } else {
            return $scheduleTimeUser->getAccept();
        }
    }
    public function scheduleUserHasVoted(User $user, Rooms $rooms): ?bool
    {
        /** @var SchedulingTimeUserRepository $schedulingTimeUserRepository */
        $schedulingTimeUserRepository = $this->em->getRepository(SchedulingTimeUser::class);
        $scheduleTimeUser = $schedulingTimeUserRepository->findVotesForUserAndRoom($rooms,$user);
        if (sizeof($scheduleTimeUser) === 0) {
            return false;
        } else {
            return true;
        }
    }

    public function scheduleNumber(SchedulingTime $schedulingTime, int $type): ?int
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findBy(['scheduleTime' => $schedulingTime, 'accept' => $type]);
        return sizeof($scheduleTimeUser);
    }

    /**
     * @return SchedulingTimeUser[]
     */
    public function scheduleUser(SchedulingTime $schedulingTime, int $type): array
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findBy(['scheduleTime' => $schedulingTime, 'accept' => $type]);
        return $scheduleTimeUser;
    }

    /**
     * @return Rooms[]
     */
    public function myScheduledMeeting(User $user): array
    {
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = $this->em->getRepository(Rooms::class);
        return $roomsRepository->getMyScheduledRooms($user);
    }
}
