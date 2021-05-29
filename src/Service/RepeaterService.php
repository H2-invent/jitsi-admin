<?php


namespace App\Service;


use App\Entity\Repeat;
use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;

class RepeaterService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    function createNewRepeater(Repeat $repeat): Repeat
    {
        switch ($repeat->getRepeatType()) {
            case 0:
                $repeat = $this->createDaily($repeat);
                break;
            case 1:
                $repeat = $this->createWeekly($repeat);
                break;
            case 2:
                $repeat = $this->createMontly($repeat);
                break;
            case 3:
                $repeat = $this->createYearly($repeat);
                break;
            default:
                break;
        }
        return $repeat;
    }

    function createDaily(Repeat $repeat): Repeat
    {
        //hier bauen wir alle X tage einen neuenRoom
        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype,$repeat, $startTmp);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeaterDays() . ' days');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    function createWeekly(Repeat $repeat): Repeat
    {
        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype,$repeat, $startTmp);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeaterDays() . ' days');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    function createMontly(Repeat $repeat): Repeat
    {
        return $repeat;
    }

    function createYearly(Repeat $repeat): Repeat
    {
        return $repeat;
    }

    function createClonedRoom(Rooms $prototype,Repeat $repeat, \DateTime $start): Rooms
    {
        $room = clone $prototype;
        $room->setUid(rand(0, 999) . time());
        $room->setUidReal(md5(uniqid()));
        $room->setUidParticipant(md5(uniqid()));
        $room->setUidModerator(md5(uniqid()));
        $room->setRepeater($repeat);
        foreach ($prototype->getPrototypeUsers() as $data) {
            $room->addUser($data);
        }
        $room->setStart($start);
        $end = clone $start;
        $end->modify('+' . $prototype->getDuration() . ' min');
        $room->setEnddate($end);
        return $room;
    }
}