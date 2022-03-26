<?php

namespace App\Service\caller;

use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;

class CallerPinService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function getPin(Rooms $rooms, $pin)
    {

    }
}