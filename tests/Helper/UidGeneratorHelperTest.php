<?php

namespace App\Tests\Helper;

use App\Enums\UidType;
use App\Helper\UidGeneratorHelper;
use PHPUnit\Framework\TestCase;

class UidGeneratorHelperTest extends TestCase
{
    public function testGenerateReturnsPrefixedRandomHex(): void
    {
        foreach (UidType::cases() as $type) {
            $uid = UidGeneratorHelper::generate($type);
            self::assertMatchesRegularExpression('/^' . $type->value . '_[0-9a-f]{64}$/', $uid);
        }
    }

    public function testEnumValuesAreUniqueAndUrlSafe(): void
    {
        $values = array_map(static fn (UidType $type): string => $type->value, UidType::cases());
        self::assertSame($values, array_unique($values));
        foreach ($values as $value) {
            self::assertMatchesRegularExpression('/^[a-z]+$/', $value);
        }
    }

    public function testGenerateProducesUniqueValues(): void
    {
        $first = UidGeneratorHelper::generate(UidType::ROOM);
        for ($i = 0; $i < 10; ++$i) {
            self::assertNotSame($first, UidGeneratorHelper::generate(UidType::ROOM));
        }
    }

    public function testParseRoundTripForAllTypes(): void
    {
        foreach (UidType::cases() as $type) {
            $parsed = UidGeneratorHelper::parse(UidGeneratorHelper::generate($type));

            self::assertSame($type, $parsed['type']);
            self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $parsed['random']);
        }
    }

    public function testParseRejectsInvalidUids(): void
    {
        $hex64 = str_repeat('0', 64);
        $invalidUids = [
            'zz_' . $hex64, // unknown prefix
            'md_' . str_repeat('G', 64), // non-hex random part
            'MD_' . $hex64, // uppercase prefix
            $hex64, // legacy md5-style value without prefix
            'md_' . str_repeat('0', 32), // legacy-length random part
        ];

        foreach ($invalidUids as $invalidUid) {
            try {
                UidGeneratorHelper::parse($invalidUid);
                self::fail('Expected InvalidArgumentException for "' . $invalidUid . '"');
            } catch (\InvalidArgumentException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }
    }

    public function testGetPrefixReturnsEnumForAllTypes(): void
    {
        foreach (UidType::cases() as $type) {
            self::assertSame($type, UidGeneratorHelper::getPrefix(UidGeneratorHelper::generate($type)));
        }
    }

    public function testGetRandomReturnsHexPart(): void
    {
        foreach (UidType::cases() as $type) {
            $uid = UidGeneratorHelper::generate($type);
            $parsed = UidGeneratorHelper::parse($uid);

            self::assertSame($parsed['random'], UidGeneratorHelper::getRandom($uid));
            self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $parsed['random']);
        }
    }
}
