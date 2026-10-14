<?php

namespace App\Tests;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\LivekitRoomNameGenerator;
use App\Service\RoomService;
use App\Service\Theme\ThemeService;
use App\Service\UserPreferenceProvider;
use DG\BypassFinals;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;



final class JWTGenerateTest extends TestCase
{
    private const APP_ID = 'test-app-id';
    private const APP_SECRET = 'test-app-secret-at-the-very-minimum-32-bytes';
    private const JWT_LIFETIME_IN_SEC = 3600;

    private ThemeService&MockObject $themeService;
    private RoomService $roomService;

    protected function setUp(): void
    {

        $this->themeService = $this->createMock(ThemeService::class);
        $userPreferences = $this->createStub(UserPreferenceProvider::class);
        $userPreferences
            ->method('getLanguage')
            ->willReturn('de');
        $userPreferences
            ->method('getTimezone')
            ->willReturn('Europe/Berlin');
        $userPreferences
            ->method('getColorScheme')
            ->willReturn('dark');

        $parameterBag = $this->createStub(ParameterBagInterface::class);
        $parameterBag
            ->method('get')
            ->willReturnMap([
                ['JWT_LIFETIME_IN_SEC', self::JWT_LIFETIME_IN_SEC],
            ]);

        $this->roomService = new RoomService(
            $this->createStub(UploaderHelper::class),
            $this->createStub(LoggerInterface::class),
            $parameterBag,
            $this->createStub(CacheInterface::class),
            $this->createStub(HttpClientInterface::class),
            $this->createStub(SluggerInterface::class),
            $userPreferences,
            $this->createStub(LivekitRoomNameGenerator::class),
            $this->themeService,
            new MockClock(),
        );
    }

    public function testGenereateJwtPayloadUsesMicrophoneAndCameraSettingsFromTheme(): void
    {
        [$room, $server] = $this->createRoomAndServer();

        $this->themeService
            ->expects(self::exactly(2))
            ->method('getThemeProperty')
            ->willReturnMap([
                ['isMicrophoneEnabled', 'true'],
                ['isCameraEnabled', 'false'],
            ]);

        $payload = $this->roomService->genereateJwtPayload(
            'Ada Lovelace',
            $room,
            $server,
            true,
        );

        $this->assertPayloadMatchesExpected($this->expectedPayload(), $payload);
    }

    public function testGenereateJwtPayloadUsesMicrophoneAndCameraSettingsFromFunction(): void
    {
        [$room, $server] = $this->createRoomAndServer();

        $this->themeService
            ->expects(self::exactly(0))
            ->method('getThemeProperty')
            ->willReturnMap([
                ['isMicrophoneEnabled', 'false'],
                ['isCameraEnabled', 'true'],
            ]);

        $payload = $this->roomService->genereateJwtPayload(
            'Ada Lovelace',
            $room,
            $server,
            true,
            enableMic: 'true',
            enableCamera: 'false'
        );

        $this->assertPayloadMatchesExpected($this->expectedPayload(), $payload);
    }
    public function testGenereateJwtPayloadUsesMicrophoneAndCameraSettingsNotSet(): void
    {
        [$room, $server] = $this->createRoomAndServer();

        $this->themeService
            ->expects(self::exactly(2))
            ->method('getThemeProperty')
            ->willReturn(null);

        $payload = $this->roomService->genereateJwtPayload(
            'Ada Lovelace',
            $room,
            $server,
            true
        );
        $expected = $this->expectedPayload();
        unset($expected['settings']);

        $this->assertPayloadMatchesExpected($expected, $payload);
    }

    public function testGenereateJwtPayloadPrefersExplicitSettingsOverTheme(): void
    {
        [$room, $server] = $this->createRoomAndServer();

        $this->themeService
            ->expects(self::never())
            ->method('getThemeProperty');

        $payload = $this->roomService->genereateJwtPayload(
            'Ada Lovelace',
            $room,
            $server,
            true,
            null,
            null,
            false,
            false,
            'false',
            'true',
        );

        $expected = $this->expectedPayload();
        $expected['settings'] = [
            'isMicrophoneEnabled' => false,
            'isCameraEnabled' => true,
        ];

        $this->assertPayloadMatchesExpected($expected, $payload);
    }

    public function testGenerateJwtSignsPayloadContainingThemeSettings(): void
    {
        [$room] = $this->createRoomAndServer();

        $this->themeService
            ->expects(self::exactly(2))
            ->method('getThemeProperty')
            ->willReturnMap([
                ['isMicrophoneEnabled', 'true'],
                ['isCameraEnabled', 'false'],
            ]);

        $jwt = $this->roomService->generateJwt(
            $room,
            null,
            'Ada Lovelace',
            true,
        );

        $decoded = json_decode(json_encode(JWT::decode($jwt, new Key(self::APP_SECRET, 'HS256'))), true);

        $this->assertPayloadMatchesExpected($this->expectedPayload(), $decoded);
    }

    private function assertPayloadMatchesExpected(array $expected, array $actual): void
    {
        self::assertArrayHasKey('iat', $actual);
        self::assertArrayHasKey('exp', $actual);
        self::assertSame(self::JWT_LIFETIME_IN_SEC, $actual['exp'] - $actual['iat']);

        unset($actual['iat'], $actual['exp']);

        self::assertSame($expected, $actual);
    }

    /**
     * @return array{0: Rooms&Stub, 1: Server&Stub}
     */
    private function createRoomAndServer(): array
    {
        $server = $this->createStub(Server::class);
        $server->method('getAppId')->willReturn(self::APP_ID);
        $server->method('getAppSecret')->willReturn(self::APP_SECRET);
        $server->method('getUrl')->willReturn('https://meet.example.test');
        $server->method('isLiveKitServer')->willReturn(false);
        $server->method('getJigasiNumberUrl')->willReturn(null);
        $server->method('getJwtModeratorPosition')->willReturn(0);
        $server->method('getFeatureEnableByJWT')->willReturn(false);

        $room = $this->createStub(Rooms::class);
        $room->method('getServer')->willReturn($server);
        $room->method('getUid')->willReturn('room-123');
        $room->method('getName')->willReturn('Architecture Review');
        $room->method('getModerator')->willReturn(null);

        return [$room, $server];
    }

    private function expectedPayload(): array
    {
        return [
            'aud' => 'jitsi_admin',
            'iss' => self::APP_ID,
            'sub' => 'https://meet.example.test',
            'room' => 'room-123',
            'context' => [
                'room' => [
                    'name' => 'Architecture Review',
                    'isE2EEEnabled' => false,
                ],
                'user' => [
                    'name' => 'Ada Lovelace',
                    'language' => 'de',
                    'timezone' => 'Europe/Berlin',
                ],
            ],
            'settings' => [
                'isMicrophoneEnabled' => true,
                'isCameraEnabled' => false,
            ],
            'moderator' => true,
            'lobbyModerator' => false,
            'theme' => [
                'colorScheme' => 'dark',
            ],
        ];
    }
}
