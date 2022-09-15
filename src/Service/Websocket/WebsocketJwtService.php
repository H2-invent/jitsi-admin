<?php

namespace App\Service\Websocket;

use App\Entity\User;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WebsocketJwtService
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function createJwt($rooms, ?User $user){
        $payload = [
            'iss' => 'jitsi-admin',
            'aud' => 'jitsi-admin',
            'sub'=> $user?$user->getUid():null,
            'status'=>$user->getOnlineStatus(),
            'iat' => (new \DateTime())->getTimestamp(),
            'nbf' => (new \DateTime())->getTimestamp(),
            'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
            'rooms'=>$rooms
        ];

        return JWT::encode($payload,$this->parameterBag->get('WEBSOCKET_SECRET'));
    }
}