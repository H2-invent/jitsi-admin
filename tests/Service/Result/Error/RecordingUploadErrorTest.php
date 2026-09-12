<?php

namespace App\Tests\Service\Result\Error;

use App\Service\Result\Error\RecordingUploadError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecordingUploadError::class)]
class RecordingUploadErrorTest extends TestCase
{
    public function testUploadIncompleteHasExpectedBackedValue(): void
    {
        self::assertSame('Incomplete upload', RecordingUploadError::UPLOAD_INCOMPLETE->value);
        self::assertSame(RecordingUploadError::UPLOAD_INCOMPLETE, RecordingUploadError::from('Incomplete upload'));
    }

    public function testExposesExactlyOneCase(): void
    {
        self::assertCount(1, RecordingUploadError::cases());
    }

    public function testTryFromUnknownValueReturnsNull(): void
    {
        self::assertNull(RecordingUploadError::tryFrom('unknown'));
    }
}
