<?php

namespace App\Tests\Service\Result\Error;

use App\Service\Result\Error\RecordingFinalizeError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecordingFinalizeError::class)]
class RecordingFinalizeErrorTest extends TestCase
{
    /**
     * @dataProvider provideCases
     */
    #[DataProvider('provideCases')]
    public function testCaseHasExpectedBackedValue(RecordingFinalizeError $case, string $expectedValue): void
    {
        self::assertSame($expectedValue, $case->value);
        self::assertSame($case, RecordingFinalizeError::from($expectedValue));
    }

    public static function provideCases(): array
    {
        return [
            'no chunks found' => [RecordingFinalizeError::NO_CHUNKS_FOUND, 'Could not find chunks on disk'],
            'no recording found' => [RecordingFinalizeError::NO_RECORDING_FOUND, 'Could not find recording via uid'],
            'could not write final file' => [RecordingFinalizeError::COULD_NOT_WRITE_FINAL_FILE, 'Could not write the final file'],
        ];
    }
}
