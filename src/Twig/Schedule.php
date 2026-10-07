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

class Schedule
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'scheduleOwnJoice')]
    public function scheduleOwnJoice(User $user, SchedulingTime $schedulingTime): ?int
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findOneBy(['user' => $user, 'scheduleTime' => $schedulingTime]);
        if (!$scheduleTimeUser) {
            return null;
        }

        return $scheduleTimeUser->getAccept();
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'scheduleUserHasVoted')]
    public function scheduleUserHasVoted(User $user, Rooms $rooms): ?bool
    {
        /** @var SchedulingTimeUserRepository $schedulingTimeUserRepository */
        $schedulingTimeUserRepository = $this->em->getRepository(SchedulingTimeUser::class);
        $scheduleTimeUser             = $schedulingTimeUserRepository->findVotesForUserAndRoom($rooms, $user);
        if (sizeof($scheduleTimeUser) === 0) {
            return false;
        }

        return true;
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'scheduleNumber')]
    public function scheduleNumber(SchedulingTime $schedulingTime, int $type): ?int
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findBy(['scheduleTime' => $schedulingTime, 'accept' => $type]);
        return sizeof($scheduleTimeUser);
    }

    /**
     * @return SchedulingTimeUser[]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'scheduleUser')]
    public function scheduleUser(SchedulingTime $schedulingTime, int $type): array
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findBy(['scheduleTime' => $schedulingTime, 'accept' => $type]);
        return $scheduleTimeUser;
    }

    /**
     * @return Rooms[]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'myScheduledMeeting')]
    public function myScheduledMeeting(User $user): array
    {
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = $this->em->getRepository(Rooms::class);
        return $roomsRepository->getMyScheduledRooms($user);
    }
}
