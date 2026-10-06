<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\RoomService;

class Jwt
{
    public function __construct(private readonly RoomService $roomService)
    {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'jwtFromRoom')]
    public function jwtFromRoom(
        ?User            $user,
        Rooms            $rooms,
        string           $name,
        bool             $moderatorExplizit = false,
        bool             $noModerator = false,
        string|bool|null $skipLobby = false,
        string|bool|null $enableMic = null,
        string|bool|null $enableCamera = null
    ): string {
        return $this->roomService->generateJwt(
            $rooms,
            $user,
            $name,
            $moderatorExplizit,
            noModerator: $noModerator,
            skipLobby: $skipLobby,
            enableMic: $enableMic,
            enableCamera: $enableCamera
        );
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'urlFromRoom')]
    public function urlFromRoom(?User $user, Rooms $rooms, string $name, string $t): string
    {
        if ($user) {
            return $this->roomService->join($rooms, $user, $t, $name);
        } else {
            return $this->roomService->joinUrl($t, $rooms, $name, false);
        }
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'generateEncryptedSecret')]
    public function generateEncryptedSecret(Rooms $rooms): ?string
    {
        return $this->roomService->generateEncryptedSecret($rooms->getServer());
    }
}
