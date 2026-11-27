<?php

namespace App\Tests\Helper;

use App\Helper\UidGeneratorHelper;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('providerForInvalidUid')]
    public function testParseRejectsInvalidUids(string $invalidUid): void
    {
        $this->expectException(\InvalidArgumentException::class);

        UidGeneratorHelper::parse($invalidUid);
    }

    public static function providerForInvalidUid(): array
    {
        return [
            'not a uuid' => ['not-a-uuid'],
            'prefixed uid' => ['rm_' . str_repeat('0', 32)],
            'hex string without dashes' => [str_repeat('0', 32)],
            'numeric string' => ['123456789abc'],
            'invalid uuid characters' => ['12345678-9abc-4def-ghij-klmnopqrstuv'],
        ];
    }
}
