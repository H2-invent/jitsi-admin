<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\RoomService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Jwt extends AbstractExtension
{
    private RoomService $roomService;
    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('jwtFromRoom', [$this, 'jwtFromRoom']),
            new TwigFunction('urlFromRoom', [$this, 'urlFromRoom']),
            new TwigFunction('generateEncryptedSecret', [$this, 'generateEncryptedSecret']),
        ];
    }

    /**
     * @param string $name
     * @param bool $moderatorExplizit
     * @param bool $noModerator
     * @param string|bool $skipLobby
     * @param string|bool|null $enableMic
     * @param string|bool|null $enableCamera
     */
    public function jwtFromRoom(?User $user, Rooms $rooms, $name, $moderatorExplizit = false,$noModerator=false, $skipLobby=false,$enableMic = null, $enableCamera=null): string
    {

        return $this->roomService->generateJwt($rooms, $user, $name, $moderatorExplizit, noModerator: $noModerator,skipLobby: $skipLobby,enableMic: $enableMic,enableCamera: $enableCamera);
    }

    /**
     * @param string $name
     * @param string $t
     */
    public function urlFromRoom(?User $user, Rooms $rooms, $name, $t): string
    {
        if ($user) {
            return $this->roomService->join($rooms, $user, $t, $name);
        } else {
            return $this->roomService->joinUrl($t, $rooms, $name, false);
        }
    }
    public function generateEncryptedSecret( Rooms $rooms): ?string
    {
        return $this->roomService->generateEncryptedSecret($rooms->getServer());
    }
}
