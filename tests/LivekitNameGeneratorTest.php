<?php

namespace App\Tests;

use App\Entity\Rooms;
use App\Service\LivekitRoomNameGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LivekitNameGeneratorTest extends KernelTestCase
{
    public function testGetLiveKitName_UsesHost(): void
    {
        // Arrange
        $roomUid = 'abc123';
        $baseUrl = 'https://base.com';
        $host = 'host.com';

        $requestStub = $this->createStub(Request::class);
        $requestStub->method('getHost')->willReturn($host);

        $requestStackStub = $this->createStub(RequestStack::class);
        $requestStackStub->method('getMainRequest')->willReturn($requestStub);

        $roomStub = $this->createStub(Rooms::class);
        $roomStub->method('getUid')->willReturn($roomUid);

        $generator = new LivekitRoomNameGenerator($baseUrl, $requestStackStub);

        // Act
        $livekitName = $generator->getLiveKitName($roomStub);

        // Assert
        $this->assertEquals('abc123@host.com', $livekitName);
    }

    public function testGetLiveKitName_UsesBaseUrl_HostIsLocalhost(): void
    {
        // Arrange
        $roomUid = 'abc123';
        $baseUrl = 'https://base.com';
        $host = 'localhost';

        $requestStub = $this->createStub(Request::class);
        $requestStub->method('getHost')->willReturn($host);

        $requestStackStub = $this->createStub(RequestStack::class);
        $requestStackStub->method('getMainRequest')->willReturn($requestStub);

        $roomStub = $this->createStub(Rooms::class);
        $roomStub->method('getUid')->willReturn($roomUid);

        $generator = new LivekitRoomNameGenerator($baseUrl, $requestStackStub);

        // Act
        $livekitName = $generator->getLiveKitName($roomStub);

        // Assert
        $this->assertEquals('abc123@base.com', $livekitName);
    }
}
