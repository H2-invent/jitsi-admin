<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Entity\Server;
use App\Entity\User;
use App\Service\LicenseService;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function GuzzleHttp\Psr7\str;

class Schedule extends AbstractExtension
{
    private $em;
    public function __construct(EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, EntityManagerInterface $em)
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
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findVotesForUserAndRoom($rooms,$user);
        if (sizeof($scheduleTimeUser) === 0) {
            return false;
        } else {
            return true;
        }
    }

    public function scheduleNumber(SchedulingTime $schedulingTime, $type): ?int
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findBy(['scheduleTime' => $schedulingTime, 'accept' => $type]);
        return sizeof($scheduleTimeUser);
    }

    public function scheduleUser(SchedulingTime $schedulingTime, $type)
    {
        $scheduleTimeUser = $this->em->getRepository(SchedulingTimeUser::class)->findBy(['scheduleTime' => $schedulingTime, 'accept' => $type]);
        return $scheduleTimeUser;
    }

    public function myScheduledMeeting(User $user)
    {
        return $this->em->getRepository(Rooms::class)->getMyScheduledRooms($user);
    }
}
