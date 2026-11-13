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

    public function testGetLiveKitName_UsesRoomHostUrl_WhenApiRequestHostDiffers(): void
    {
        // Arrange
        $roomUid = 'abc123';
        $baseUrl = 'https://app.meetling.de';
        $apiHost = 'app.meetling.de';
        $roomHost = 'meet.meetling.de';

        $requestStub = $this->createStub(Request::class);
        $requestStub->method('getHost')->willReturn($apiHost);

        $requestStackStub = $this->createStub(RequestStack::class);
        $requestStackStub->method('getMainRequest')->willReturn($requestStub);

        $roomStub = $this->createStub(Rooms::class);
        $roomStub->method('getUid')->willReturn($roomUid);
        $roomStub->method('getHostUrl')->willReturn('https://' . $roomHost);

        $generator = new LivekitRoomNameGenerator($baseUrl, $requestStackStub);

        // Act
        $livekitName = $generator->getLiveKitName($roomStub);

        // Assert
        $this->assertEquals('abc123@meet.meetling.de', $livekitName);
    }

    public function testGetLiveKitName_UsesRequestHost_WhenRoomHostUrlIsPrivate(): void
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
        $roomStub->method('getHostUrl')->willReturn('http://localhost:8000');

        $generator = new LivekitRoomNameGenerator($baseUrl, $requestStackStub);

        // Act
        $livekitName = $generator->getLiveKitName($roomStub);

        // Assert
        $this->assertEquals('abc123@host.com', $livekitName);
    }

    public function testGetLiveKitName_UsesRoomHostUrl_WithoutRequest(): void
    {
        // Arrange — server-to-server context (Messenger, cron) has no main request
        $roomUid = 'abc123';
        $baseUrl = 'https://base.com';
        $roomHost = 'meet.meetling.de';

        $requestStackStub = $this->createStub(RequestStack::class);
        $requestStackStub->method('getMainRequest')->willReturn(null);

        $roomStub = $this->createStub(Rooms::class);
        $roomStub->method('getUid')->willReturn($roomUid);
        $roomStub->method('getHostUrl')->willReturn('https://' . $roomHost);

        $generator = new LivekitRoomNameGenerator($baseUrl, $requestStackStub);

        // Act
        $livekitName = $generator->getLiveKitName($roomStub);

        // Assert
        $this->assertEquals('abc123@meet.meetling.de', $livekitName);
    }

    public function testGetLiveKitName_UsesBaseUrl_WithoutRequestAndWithoutRoomHost(): void
    {
        // Arrange — worst-case server-to-server call with no domain anywhere
        $roomUid = 'abc123';
        $baseUrl = 'https://base.com';

        $requestStackStub = $this->createStub(RequestStack::class);
        $requestStackStub->method('getMainRequest')->willReturn(null);

        $roomStub = $this->createStub(Rooms::class);
        $roomStub->method('getUid')->willReturn($roomUid);

        $generator = new LivekitRoomNameGenerator($baseUrl, $requestStackStub);

        // Act
        $livekitName = $generator->getLiveKitName($roomStub);

        // Assert
        $this->assertEquals('abc123@base.com', $livekitName);
    }

    public function testGetLiveKitName_UsesRequestHost_WhenRoomHostUrlIsMalformed(): void
    {
        // Arrange — a hostUrl without scheme cannot be parsed, fall back to the request
        $roomUid = 'abc123';
        $baseUrl = 'https://base.com';
        $host = 'host.com';

        $requestStub = $this->createStub(Request::class);
        $requestStub->method('getHost')->willReturn($host);

        $requestStackStub = $this->createStub(RequestStack::class);
        $requestStackStub->method('getMainRequest')->willReturn($requestStub);

        $roomStub = $this->createStub(Rooms::class);
        $roomStub->method('getUid')->willReturn($roomUid);
        $roomStub->method('getHostUrl')->willReturn('blah.com');

        $generator = new LivekitRoomNameGenerator($baseUrl, $requestStackStub);

        // Act
        $livekitName = $generator->getLiveKitName($roomStub);

        // Assert
        $this->assertEquals('abc123@host.com', $livekitName);
    }
}
