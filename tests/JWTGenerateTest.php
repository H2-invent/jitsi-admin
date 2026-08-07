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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;



final class JWTGenerateTest extends TestCase
{
    private const APP_ID = 'test-app-id';
    private const APP_SECRET = 'test-app-secret';

    private ThemeService&MockObject $themeService;
    private RoomService $roomService;
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }
    protected function setUp(): void
    {

        $this->themeService = $this->createMock(ThemeService::class);
        $userPreferences = $this->createMock(UserPreferenceProvider::class);
        $userPreferences
            ->method('getLanguage')
            ->willReturn('de');
        $userPreferences
            ->method('getTimezone')
            ->willReturn('Europe/Berlin');
        $userPreferences
            ->method('getColorScheme')
            ->willReturn('dark');

        $this->roomService = new RoomService(
            $this->createMock(UploaderHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(ParameterBagInterface::class),
            $this->createMock(CacheInterface::class),
            $this->createMock(HttpClientInterface::class),
            $this->createMock(SluggerInterface::class),
            $userPreferences,
            $this->createMock(LivekitRoomNameGenerator::class),
            $this->themeService,
        );
    }

    public function testGenereateJwtPayloadUsesMicrophoneAndCameraSettingsFromTheme(): void
    {
        [$room, $server] = $this->createRoomAndServer();

        $this->themeService
            ->expects(self::exactly(4))
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

        self::assertSame($this->expectedPayload(), $payload);
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

        self::assertSame($this->expectedPayload(), $payload);
    }
    public function testGenereateJwtPayloadUsesMicrophoneAndCameraSettingsNotSet(): void
    {
        [$room, $server] = $this->createRoomAndServer();



        $payload = $this->roomService->genereateJwtPayload(
            'Ada Lovelace',
            $room,
            $server,
            true
        );
        $expected = $this->expectedPayload();
        unset($expected['settings']);

        self::assertSame($expected, $payload);
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

        self::assertSame($expected, $payload);
    }

    public function testGenerateJwtSignsPayloadContainingThemeSettings(): void
    {
        [$room] = $this->createRoomAndServer();

        $this->themeService
            ->expects(self::exactly(4))
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

        self::assertSame(
            JWT::encode($this->expectedPayload(), self::APP_SECRET, 'HS256'),
            $jwt,
        );
    }

    /**
     * @return array{0: Rooms&MockObject, 1: Server&MockObject}
     */
    private function createRoomAndServer(): array
    {
        $server = $this->createMock(Server::class);
        $server->method('getAppId')->willReturn(self::APP_ID);
        $server->method('getAppSecret')->willReturn(self::APP_SECRET);
        $server->method('getUrl')->willReturn('https://meet.example.test');
        $server->method('isLiveKitServer')->willReturn(false);
        $server->method('getJigasiNumberUrl')->willReturn(null);
        $server->method('getJwtModeratorPosition')->willReturn(0);
        $server->method('getFeatureEnableByJWT')->willReturn(false);

        $room = $this->createMock(Rooms::class);
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
            'theme' => [
                'colorScheme' => 'dark',
            ],
        ];
    }
}

