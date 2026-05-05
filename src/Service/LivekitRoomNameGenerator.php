<?php

namespace App\Service;

use App\Entity\Rooms;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LivekitRoomNameGenerator
{

    private $baseUrl;

    public function __construct(
        private ParameterBagInterface $parameterBag
    )
    {
        $this->baseUrl =str_replace('https://','',$this->parameterBag->get('laF_baseUrl')) ;
        $this->baseUrl = str_replace('http://','',$this->baseUrl);
    }
    public function getLiveKitName(Rooms $rooms)
    {
        return $rooms->getUid().'@'.$this->baseUrl;
    }
}