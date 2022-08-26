<?php

namespace App\Service\Websocket;

use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WebsocketJwtService
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function createJwt($rooms, $userUid){
        $payload = [
            'iss' => 'jitsi-admin',
            'aud' => 'jitsi-admin',
            'sub'=> $userUid,
            'iat' => (new \DateTime())->getTimestamp(),
            'nbf' => (new \DateTime())->getTimestamp(),
            'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
            'rooms'=>$rooms
        ];

        return JWT::encode($payload,$this->parameterBag->get('WEBSOCKET_SECRET'));
    }
}