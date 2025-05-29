<?php

namespace App\Tests;

use App\Entity\Rooms;
use App\Service\LivekitRoomNameGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LivekitNameGeneratorTest extends KernelTestCase
{
    public function testGetLiveKitName()
    {
        // Arrange
        $roomUid = 'abc123';
        $baseUrl = 'https://example.com';

        $parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $parameterBagMock->method('get')
            ->with('laF_baseUrl')
            ->willReturn($baseUrl);

        $roomMock = $this->createMock(Rooms::class);
        $roomMock->method('getUid')
            ->willReturn($roomUid);

        $generator = new LivekitRoomNameGenerator($parameterBagMock);

        // Act
        $livekitName = $generator->getLiveKitName($roomMock);

        // Assert
        $this->assertEquals('abc123@example.com', $livekitName);
    }
}
