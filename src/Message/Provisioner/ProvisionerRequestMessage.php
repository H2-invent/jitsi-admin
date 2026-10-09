<?php

namespace App\Message\Provisioner;

use App\Message\Provisioner\Enum\ServerFeature;
use App\Message\Provisioner\Enum\Type;
use JsonSerializable;

final class ProvisionerRequestMessage implements JsonSerializable
{
    public function __construct(
        public readonly string $room_id,
        public readonly Type $type,
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
