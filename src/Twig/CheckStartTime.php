<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\StartMeetingService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


class CheckStartTime extends AbstractExtension
{


    public function __construct( private TranslatorInterface $translator)
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('isRoomOpen', [$this, 'isRoomOpen']),
        ];
    }

    public function isRoomOpen(Rooms $room, ?User $user)
    {

        $isOpen = StartMeetingService::checkTime($room, $user);
        if (!$isOpen) {
            return $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                array(
                    '{from}' => $room->getStartwithTimeZone($user)->modify('-30min')->format('d.m.Y H:i'),
                    '{to}' => $room->getEndwithTimeZone($user)->format('d.m.Y H:i')
                ));
        } else {
            return true;
        }
    }
}
