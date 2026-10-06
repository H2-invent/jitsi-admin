<?php

namespace App\Service\Whiteboard;

use App\Entity\Rooms;
use App\Helper\UidHelper;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WhiteboardJwtService
{
    public function __construct(private readonly ParameterBagInterface $parameterBag, private readonly UidHelper $uidHelper)
    {
    }

    public function createJwt(Rooms $rooms, bool $isModerator = false): string
    {
        $ui      = $this->uidHelper->getUid($rooms);
        $payload = [
            'iat'   => new \DateTimeImmutable()->getTimestamp(),
            'exp'   => new \DateTimeImmutable()->modify('+3days')->getTimestamp(),
            'roles' => [($isModerator ? 'moderator' : 'editor') . ':' . $ui]
        ];
        /** @var string $secret */
        $secret = $this->parameterBag->get('WHITEBOARD_SECRET');

        return JWT::encode($payload, $secret, 'HS256');
    }
}
