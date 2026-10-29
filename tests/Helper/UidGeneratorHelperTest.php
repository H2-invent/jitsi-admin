<?php

namespace App\Tests\Helper;

use App\Helper\UidGeneratorHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UidGeneratorHelperTest extends TestCase
{
    public function testGenerateReturnsUuidV4(): void
    {
        $uid = UidGeneratorHelper::generate();
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uid
        );
    }

    public function testGenerateProducesUniqueValues(): void
    {
        $first = UidGeneratorHelper::generate();
        for ($i = 0; $i < 10; ++$i) {
            self::assertNotSame($first, UidGeneratorHelper::generate());
        }
    }

    public function testParseReturnsUuid(): void
    {
        $uid = UidGeneratorHelper::generate();
        $uuid = UidGeneratorHelper::parse($uid);

        self::assertInstanceOf(Uuid::class, $uuid);
        self::assertSame($uid, $uuid->toRfc4122());
    }

    public function testParseRejectsInvalidUids(): void
    {
        $invalidUids = [
            'not-a-uuid',
            'rm_' . str_repeat('0', 32),
            str_repeat('0', 32),
            '123456789abc',
            '12345678-9abc-4def-ghij-klmnopqrstuv',
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
}
