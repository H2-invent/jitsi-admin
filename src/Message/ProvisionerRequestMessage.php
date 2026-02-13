<?php

namespace App\Message;

use App\Message\ProvisionerRequest\RequestType;
use App\Message\ProvisionerRequest\ServerFeature;

final class ProvisionerRequestMessage
{
    public function __construct(
        public readonly string $room_id,
        public readonly RequestType $type,
        /** @var ServerFeature[] */
        public readonly ?array $server_features = null,
    )
    {
    }
}
