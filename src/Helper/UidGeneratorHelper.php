<?php

namespace App\Helper;

use Symfony\Component\Uid\Uuid;

class UidGeneratorHelper
{
    public static function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public static function parse(string $uid): Uuid
    {
        return Uuid::fromString($uid);
    }
}
