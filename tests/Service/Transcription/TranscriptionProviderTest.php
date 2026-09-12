<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription;

use App\Service\Transcription\TranscriptionProvider;
use PHPUnit\Framework\TestCase;

class TranscriptionProviderTest extends TestCase
{
    public function testCasesExposeExpectedLabels(): void
    {
        $this->assertSame('Open AI: Whisper-1', TranscriptionProvider::OPEN_AI_WHISPER->value);
        $this->assertSame('Mistral: Voxtral Mini Transcribe 2', TranscriptionProvider::MISTRAL_VOXTRAL_MINI->value);
    }

    public function testFromResolvesLabelsToCases(): void
    {
        $this->assertSame(
            TranscriptionProvider::OPEN_AI_WHISPER,
            TranscriptionProvider::from('Open AI: Whisper-1'),
        );
        $this->assertSame(
            TranscriptionProvider::MISTRAL_VOXTRAL_MINI,
            TranscriptionProvider::from('Mistral: Voxtral Mini Transcribe 2'),
        );
    }

    public function testTryFromReturnsNullForUnknownLabel(): void
    {
        $this->assertNull(TranscriptionProvider::tryFrom('does not exist'));
    }

    public function testCasesAreComplete(): void
    {
        $this->assertSame(
            [TranscriptionProvider::OPEN_AI_WHISPER, TranscriptionProvider::MISTRAL_VOXTRAL_MINI],
            TranscriptionProvider::cases(),
        );
    }
}
