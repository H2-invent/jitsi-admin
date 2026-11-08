<?php
declare(strict_types=1);

namespace App\Service\livekit;

use App\Entity\Server;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use JsonException;

class LivekitJwtService
{
    public function getDecryptedJwt(string $jwt, Server $server): ?array
    {
        $secret = $server->getAppSecret();

        try {
            $jwtDecoded = JWT::decode($jwt, new Key($secret, 'HS256'));
            // little hack to create array from deeply nested stdClass
            $jwtArray = json_decode(json_encode($jwtDecoded, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException | SignatureInvalidException | DomainException $e) {
            return null;
        }

        return $jwtArray;
    }
}
