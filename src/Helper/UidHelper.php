<?php

namespace App\Helper;

use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;

class UidHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {
    }

    public function getUid(Rooms $rooms): string
    {
        $ui = $rooms->getUidReal();
        if ($rooms->getRepeater()) {
            if (!$rooms->getRepeater()->getUid()) {
                $rep = $rooms->getRepeater();
                $rep->setUid(md5(uniqid()));
                $this->entityManager->persist($rep);
                $this->entityManager->flush();
            }

            $ui = $rooms->getRepeater()->getUid();
        }
        return $ui;
    }
}
