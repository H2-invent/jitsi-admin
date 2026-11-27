<?php

namespace App\Helper;

use App\Entity\Rooms;
use App\Helper\UidGeneratorHelper;
use Doctrine\ORM\EntityManagerInterface;

class UidHelper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getUid(Rooms $rooms): string
    {
        $ui = $rooms->getUidReal();
        if ($rooms->getRepeater()) {
            $repeat = $rooms->getRepeater();
            if (!$repeat->getUid()) {
                $repeat->setUid(UidGeneratorHelper::generate());
                $this->entityManager->persist($repeat);
            }

            $ui = $repeat->getUid();
        }

        return $ui;
    }
}
