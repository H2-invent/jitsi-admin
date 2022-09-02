<?php

namespace App\Service\Whiteboard;

use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WhiteboardJwtService
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function createJwt($isModerator):string{
        $payload = [
            'iat' => (new \DateTime())->getTimestamp(),
            'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
            'roles' => array($isModerator?'moderator':'')
        ];

        return JWT::encode($payload,$this->parameterBag->get('WHITEBOARD_SECRET'));
    }
}