<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SchedulingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserService            $userService,
        private TranslatorInterface    $translator,
        private Environment            $environment,
        private MailerService          $mailerService,
    )
    {
    }

    public function chooseTimeSlot(SchedulingTime $schedulingTime): ?bool
    {
        $room = $schedulingTime->getScheduling()->getRoom();
        $room->setScheduleMeeting(false);
        $room->setStart($schedulingTime->getTime());
        $end = clone $schedulingTime->getTime();
        $end->modify('+' . $room->getDuration() . 'min');
        $room->setEnddate($end);
        $this->em->persist($room);
        $this->em->flush();
        try {
            foreach ($room->getUser() as $data) {
                $this->userService->addUser($data, $room);
            }
        } catch (Exception) {
            return false;
        }
        return true;
    }

    public function createScheduling(Rooms $rooms): void
    {
        if (sizeof($rooms->getSchedulings()->toArray()) < 1) {
            $schedule = new Scheduling();
            $schedule->setUid(md5(uniqid()));
            $schedule->setRoom($rooms);
            $this->em->persist($schedule);
            $this->em->flush();
            $rooms->addScheduling($schedule);
            $this->em->persist($rooms);
            $this->em->flush();
        }
    }

    public function sendEmailWhenNewSchedulingTime(SchedulingTime $schedulingTime)
    {
        $room = $schedulingTime->getScheduling()->getRoom();
        $subject = $this->translator->trans('scheduling.new.schedulingTime.subject');
        foreach ($schedulingTime->getScheduling()->getRoom()->getUser() as $user){
            $content = $this->environment->render('email/newSchedulingTime.html.twig',['room'=>$room,'user'=>$user]);
            if ($schedulingTime->getCreatedFrom()){
                if ($schedulingTime->getCreatedFrom() !== $user){
                    $this->mailerService->sendEmail(
                        user: $user,
                        betreff: $subject,
                        content: $content,
                        server: $room->getServer(),
                        replyTo: $room->getModerator()->getEmail(),
                        rooms: $room
                    );
                }
            }else{
                if ($schedulingTime->getScheduling()->getRoom()->getModerator()!== $user){
                    $this->mailerService->sendEmail(
                        user: $user,
                        betreff: $subject,
                        content: $content,
                        server: $room->getServer(),
                        replyTo: $room->getModerator()->getEmail(),
                        rooms: $room
                    );
                }
            }
        }

    }
}
