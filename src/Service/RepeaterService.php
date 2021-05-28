<?php


namespace App\Service;


use App\Entity\Repeat;
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
        $this->em->flush();
    }
    function createDaily(Repeat  $repeat){
        //hier bauen wir alle X tage einen neuenRoom
        for ($i = 0; $i < $repeat->getRepetation();$i++){

        }
    }
    function createWeekly(Repeat  $repeat){

    }
    function createMontly(Repeat  $repeat){

    }
    function createYearly(Repeat  $repeat){

    }
}