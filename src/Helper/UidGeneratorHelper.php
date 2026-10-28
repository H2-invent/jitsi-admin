<?php

namespace App\Helper;

use App\Enums\UidType;

class UidGeneratorHelper
{
    public static function generate(UidType $type): string
    {
        return $type->value . '_' . bin2hex(random_bytes(32));
    }

    /**
     * @return array{type: UidType, random: string}
     */
    public static function parse(string $uid): array
    {
        if (!preg_match('/^([a-z]{3})_([0-9a-f]{64})$/', $uid, $matches)) {
            throw new \InvalidArgumentException(sprintf('Malformed uid "%s".', $uid));
        }

        $type = UidType::tryFrom($matches[1]);
        if ($type === null) {
            throw new \InvalidArgumentException(sprintf('Unknown uid prefix "%s".', $matches[1]));
        }

        return ['type' => $type, 'random' => $matches[2]];
    }

    public static function getPrefix(string $uid): UidType
    {
        return self::parse($uid)['type'];
    }

    public static function getRandom(string $uid): string
    {
        return self::parse($uid)['random'];
    }
}
