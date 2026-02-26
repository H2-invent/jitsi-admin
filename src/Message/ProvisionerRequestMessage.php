<?php

namespace App\Message;

use App\Message\ProvisionerRequest\RequestType;
use App\Message\ProvisionerRequest\ServerFeature;
use JsonSerializable;

final class ProvisionerRequestMessage implements JsonSerializable
{
    public function __construct(
        public readonly string $room_id,
        public readonly RequestType $type,
        /** @var ServerFeature[] */
        public readonly ?array $server_features = null,
    )
    {
    }

    public function jsonSerialize(): array
    {
        $json = [
            'room_id' => $this->room_id,
            'type' => $this->type,
        ];
        if ($this->server_features !== null) {
            $json['server_features'] = $this->server_features;
        }

        return $json;
    }
}
