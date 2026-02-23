<?php

namespace App\Message;

use App\Message\ProvisionerStatus\Status;

final class ProvisionerStatusMessage implements \JsonSerializable
{
    public function __construct(
        public readonly string $room_id,
        public readonly Status $status,
        public readonly ?string $name = null,
        public readonly ?string $app_id = null,
        public ?string $app_secret = null,
        public readonly ?string $url = null,
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'room_id' => $this->room_id,
            'status' => $this->status->value,
            'name' => $this->name,
            'app_id' => $this->app_id,
            'app_secret' => $this->app_secret,
            'url' => $this->url,
        ];
    }
}
