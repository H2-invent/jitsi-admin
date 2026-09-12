<?php
declare(strict_types=1);

namespace App\Tests\Support;

use FFMpeg\Driver\FFMpegDriver;
use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Format;
use FFMpeg\Media\Audio;

trait AudioMockTrait
{
    private function createAudioMock(float $duration, array &$savedPaths): Audio
    {
        $audio = $this->getMockBuilder(Audio::class)
            ->setConstructorArgs([
                '/tmp/source.mp3',
                $this->createMock(FFMpegDriver::class),
                $this->createMock(FFProbe::class),
            ])
            ->onlyMethods(['save', 'getFormat'])
            ->getMock();

        $audio->method('getFormat')->willReturn(new Format(['duration' => $duration]));
        $audio->method('save')->willReturnCallback(
            function ($format, string $path) use (&$savedPaths, $audio) {
                file_put_contents($path, 'chunk');
                $savedPaths[] = $path;
                return $audio;
            }
        );

        return $audio;
    }
}
