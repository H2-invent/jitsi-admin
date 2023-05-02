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

        $error = [];
        if (!$room->getStart() && !$room->getPersistantRoom()) {
            $error[] = $this->translator->trans('Fehler, das Startdatum darf nicht leer sein');
        }
        if (!$room->getName()) {
            $error[] = $this->translator->trans('Fehler, der Name darf nicht leer sein');
        }

        $room = $this->setRoomProps($room);
        if ($room->getStart()) {
            $now = (new \DateTime())->getTimestamp();
            $start = (new \DateTime($room->getStart()->format('Y-m-d H:i:s'), $room->getTimeZone() ? new \DateTimeZone($room->getTimeZone()) : null))->getTimestamp();
            $end = (new \DateTime((clone $room->getStart())->modify('+' . $room->getDuration() . 'min')->format('Y-m-d H:i:s'), $room->getTimeZone() ? new \DateTimeZone($room->getTimeZone()) : null))->getTimestamp();
            if (($start < $now && $end < $now) && !$room->getPersistantRoom()) {
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
            if ($room->getStart()) {
                $room->setEnddate((clone $room->getStart())->modify('+ ' . $room->getDuration() . ' minutes'));
            }
        }
        return $room;
    }
}
