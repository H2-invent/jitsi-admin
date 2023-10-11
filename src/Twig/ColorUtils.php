<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\LobbyWaitungUser;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\ExternalApplication;
use App\Service\MessageService;
use App\Service\ParticipantSearchService;
use OzdemirBurak\Iris\Color\Hex;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class ColorUtils extends AbstractExtension
{


    public function getFilters(): array
    {

        return [
            new TwigFilter('color_lighten', [$this, 'color_lighten']),

        ];
    }
    public function color_lighten($color,$percent):string{
        try {
            $hex = new Hex(trim($color));
            return $hex->brighten($percent);
        }catch (\Exception $exception){
            return $color;
        }

    }

}
