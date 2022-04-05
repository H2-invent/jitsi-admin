<?php

namespace App\Service;

use App\Entity\Rooms;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomCheckService
{
    private $translator;
    private $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $this->translator = $translator;
        $this->em = $entityManager;
    }

    public function checkRoom(Rooms $room, &$error)
    {
        $now = new \DateTime();
        $error = array();
        if (!$room->getStart() && !$room->getPersistantRoom()) {
            $error[] = $this->translator->trans('Fehler, das Startdatum darf nicht leer sein');
        }
        if (!$room->getName()) {
            $error[] = $this->translator->trans('Fehler, der Name darf nicht leer sein');
        }

        $room = $this->setRoomProps($room);
        if ($room->getStart()) {
            if (($room->getStart() < $now && $room->getEnddate() < $now) && !$room->getPersistantRoom()) {
                $error[] = $this->translator->trans('Fehler, das Startdatum und das Enddatum liegen in der Vergangenheit');
            }
        }
        return $room;
    }

    function setRoomProps(Rooms $room)
    {
        if ($room->getPersistantRoom()) {
            $counter = 0;
            $slug = UtilsHelper::slugify($room->getName());
            $tmp = $slug . '-' . rand(10, 1000);
            if (!$room->getSlug()) {
                while (true) {
                    $roomTmp = $this->em->getRepository(Rooms::class)->findOneBy(['uid' => $tmp]);
                    if (!$roomTmp) {
                        $room->setUid($tmp);
                        $room->setSlug($tmp);
                        break;
                    } else {
                        $counter++;
                        $tmp = $slug . '-' . rand(10, 1000);
                    }
                }
            }
            $room->setStart(null);
            $room->setEnddate(null);

        } else {
            if($room->getStart()){
                $room->setEnddate((clone $room->getStart())->modify('+ ' . $room->getDuration() . ' minutes'));
            }
        }
        return $room;
    }
}