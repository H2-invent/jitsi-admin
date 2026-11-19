<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RoomkitService
{
    public function __construct(private readonly RoomService $roomService) {
    }

    /**
     * Generates a slim JWT
     * The Room Kit WebView only accepts a 2000 chars long URL. So we need a shorter JWT
     */
    public function generateJwt(Rooms $room, User $user, string $userName): string
    {
        $jwt = $this->roomService->generateJwt($room, $user, $userName, skipLobby: true);

        if ($jwt === '') {
            return $jwt;
        }

        $appSecret = $room->getServer()->getAppSecret();
        $payload = json_decode(json_encode(JWT::decode($jwt, new Key($appSecret, 'HS256')), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        unset(
            $payload['backgroundImages'],
            $payload['context']['lobby']['chatMessages'],
            $payload['context']['user']['hotstandby'],
            $payload['context']['user']['avatarAway'],
        );
        $payload['context']['user']['skipPrejoin'] = true;
        $payload['context']['user']['device'] = ['type' => 'roomkit'];
        $payload['theme']['colorScheme'] = 'dark';
        $payload['settings']['isCameraEnabled'] = true;
        $payload['settings']['isMicrophoneEnabled'] = true;

        return JWT::encode($payload, $appSecret, 'HS256');
    }
}
