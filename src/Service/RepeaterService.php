<?php


namespace App\Service;


use App\Entity\Repeat;
use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;

class RepeaterService
{
    private $em;
    public function __construct(EntityManagerInterface  $entityManager)
    {
        $this->em = $entityManager;
    }

    function createNewRepeater( Repeat $repeat){
        switch ($repeat->getRepeatType()){
            case 0:
                $this->createDaily($repeat);
                break;
            case 1:
                $this->createWeekly($repeat);
                break;
            case 2:
                $this->createMontly($repeat);
                break;
            case 3:
                $this->createYearly($repeat);
                break;
            default:
                break;
        }
        $this->em->persist($repeat);
      //  $this->em->flush();
    }
    function createDaily(Repeat  $repeat):Repeat{
        //hier bauen wir alle X tage einen neuenRoom
        $start = $repeat->getStartDate();
        $prototype = $repeat->getPrototyp();
        $start->setTime($prototype->getStart()->format('H'),$prototype->getStart()->format('i'));

        for ($i = 0; $i < $repeat->getRepetation();$i++){
            $room = clone $prototype;
            $room->setUid(rand(0,999).time());
            $room->setUidReal(md5(uniqid()));
            $room->setUidParticipant(md5(uniqid()));
            $room->setUidModerator(md5(uniqid()));
            $room->setRepeater($repeat);
            foreach ($prototype->getPrototypeUsers() as $data){
                $room->addUser($data);
            }
            $startTmp = clone $start;
            $room->setStart($startTmp);
            $end = clone $startTmp;
            $end->modify('+'.$prototype->getDuration().' min');
            $room->setEnddate($end);
            $this->em->persist($room);
            $start->modify('+'.$repeat->getRepeaterDays().' days');

        }
        $this->em->persist($repeat);
        $this->em->flush();
        return $repeat;
    }
    function createWeekly(Repeat  $repeat){

    }
    function createMontly(Repeat  $repeat){

    }
    function createYearly(Repeat  $repeat){

    }
}