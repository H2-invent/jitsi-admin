<?php


namespace App\Service;


use App\Entity\Repeat;
use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;

class RepeaterService
{
    private $em;
    private $days = array(
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        0 => 'Sunday'
    );
    private $number = array(
        0 => 'First',
        1 => 'Second',
        2 => 'Third',
        3 => 'Fourth',
        4 => 'Fifth',
        5 => 'Last',
    );
    private $months = array(
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July ',
        'August',
        'September',
        'October',
        'November',
        'December',
    );

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
                $repeat = $this->createMontlyRelative($repeat);
                break;
            case 4:
                $repeat = $this->createYearly($repeat);
                break;
            case 5:
                $repeat = $this->createYearlyRelative($repeat);
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
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
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
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeaterWeeks() . ' weeks');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    function createMontly(Repeat $repeat): Repeat
    {

        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeatMontly() . ' months');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    function createMontlyRelative(Repeat $repeat): Repeat
    {

        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
        $startTmp = clone $start;
        $startTmp->modify('first day of this month');
        $text = $this->number[$repeat->getRepatMonthRelativNumber()] . ' ' . $this->days[$repeat->getRepatMonthRelativWeekday()] . ' of this month';
        $startTmp->modify($text);
        $sollCounter = $repeat->getRepetation();
        if ($startTmp > $start) {
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
            $sollCounter--;
        }

        for ($i = 0; $i < $sollCounter; $i++) {
            $start->modify('first day of next Month');
            if ($repeat->getRepeatMonthlyRelativeHowOften() > 1) {
                $start->modify('+' . ($repeat->getRepeatMonthlyRelativeHowOften() - 1) . ' months');
            }
            $start->modify($text);
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    function createYearly(Repeat $repeat): Repeat
    {
        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation(); $i++) {
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
            $start->modify('+' . $repeat->getRepeatYearly() . ' years');
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    function createYearlyRelative(Repeat $repeat): Repeat
    {

        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'), $prototype->getStart()->format('i'));
        $startTmp = clone $start;
        $startTmp->modify('first day of this year');
        $text = $this->number[$repeat->getRepeatYearlyRelativeNumber()] . ' ' . $this->days[$repeat->getRepeatYearlyRelativeWeekday()] . ' of '.$this->months[$repeat->getRepeatYearlyRelativeMonth()];
        $startTmp->modify($text);
        $sollCounter = $repeat->getRepetation();
        if ($startTmp > $start) {
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
            $sollCounter--;
        }

        for ($i = 0; $i < $sollCounter; $i++) {
            $start->modify('first day of next Year');
            if ($repeat->getRepeatYearlyRelativeHowOften() > 1) {
                $start->modify('+' . ($repeat->getRepeatYearlyRelativeHowOften() - 1) . ' years');
            }
            $start->modify($text);
            $startTmp = clone $start;
            $room = $this->createClonedRoom($prototype, $repeat, $startTmp);
            $this->em->persist($room);
        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }

    function createClonedRoom(Rooms $prototype, Repeat $repeat, \DateTime $start): Rooms
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