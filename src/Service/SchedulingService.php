<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use Doctrine\ORM\EntityManagerInterface;

class SchedulingService
{
    private $em;
    private $userService;

    public function __construct(EntityManagerInterface $entityManager,UserService $userService)
    {
        $this->em = $entityManager;
        $this->userService = $userService;
    }
    public function chooseTimeSlot(SchedulingTime $schedulingTime):?bool{
        $room = $schedulingTime->getScheduling()->getRoom();
        $room->setScheduleMeeting(false);
        $room->setStart($schedulingTime->getTime());
        $end = clone $schedulingTime->getTime();
        $end->modify('+'.$room->getDuration().'min');
        $room->setEnddate($end);
        $this->em->persist($room);
        $this->em->flush();
        try {
            foreach ($room->getUser() as $data){
                $this->userService->addUser($data,$room);
            }
        }catch (\Exception $exception){
            return false;
        }
        return true;
    }
    public function createScheduling(Rooms $rooms){
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
}