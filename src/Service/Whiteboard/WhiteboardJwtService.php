<?php

namespace App\Service\Whiteboard;

use App\Entity\Rooms;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WhiteboardJwtService
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function createJwt(Rooms $rooms, $isModerator):string{
        $payload = [
            'iat' => (new \DateTime())->getTimestamp(),
            'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
            'roles' => array(($isModerator?'moderator':'editor').':'.$rooms->getUidReal())
        ];
        return JWT::encode($payload,$this->parameterBag->get('WHITEBOARD_SECRET'));
    }
}