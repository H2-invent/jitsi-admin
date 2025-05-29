<?php

namespace App\Tests;

use App\Entity\Rooms;
use App\Service\LivekitRoomNameGenerator;
use App\Twig\Runtime\LivekitUrlRuntime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LiveKitUrlGeneratorTwigExtensionTest extends KernelTestCase
{
    public function testGetLiveKitName()
    {
        // Arrange
        $roomUid = 'room123';
        $expectedResult = 'room123@domain.test';

        $roomMock = $this->createMock(Rooms::class);
        $roomMock->method('getUid')->willReturn($roomUid);

        $generatorMock = $this->createMock(LivekitRoomNameGenerator::class);
        $generatorMock->method('getLiveKitName')->with($roomMock)->willReturn($expectedResult);

        $runtime = new LivekitUrlRuntime($generatorMock);

        // Act
        $result = $runtime->getLiveKitName($roomMock);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }
}
