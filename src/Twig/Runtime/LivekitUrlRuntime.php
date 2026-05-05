<?php

namespace App\Twig\Runtime;

use App\Entity\Rooms;
use App\Service\LivekitRoomNameGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\RuntimeExtensionInterface;

class LivekitUrlRuntime implements RuntimeExtensionInterface
{

    public function __construct(
        private LivekitRoomNameGenerator $livekitRoomNameGenerator,
    )
    {
    }

    public function getLiveKitName(Rooms $rooms)
    {
     return  $this->livekitRoomNameGenerator->getLiveKitName($rooms);
    }
}
