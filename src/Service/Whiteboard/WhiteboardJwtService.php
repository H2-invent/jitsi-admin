<?php

namespace App\Service\Whiteboard;

use App\Entity\Rooms;
use App\Helper\ExternalApplication;
use App\Helper\UidHelper;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WhiteboardJwtService
{
    public function __construct(private ParameterBagInterface $parameterBag, private UidHelper $uidHelper)
    {
    }

    public function createJwt(Rooms $rooms, $isModerator = false): string
    {
        $ui = $this->uidHelper->getUid($rooms);
        $payload = [
            'iat' => (new \DateTime())->getTimestamp(),
            'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
            'roles' => [($isModerator ? 'moderator' : 'editor') . ':' . $ui]
        ];
        return JWT::encode($payload, $this->parameterBag->get('WHITEBOARD_SECRET'),'HS256');
    }
}
