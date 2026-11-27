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
}
