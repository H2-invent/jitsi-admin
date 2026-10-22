<?php

declare(strict_types=1);

namespace App\Tests\Theme;

use App\Service\Theme\ThemeService;
use H2Entwicklung\Signature\CheckSignature;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ThemeAssetsTest extends TestCase
{
    private const GETTERS = [
        'THEME_HEADER_CSS' => 'getHeaderCss',
        'THEME_HEADER_JS' => 'getHeaderJs',
        'THEME_FOOTER_CSS' => 'getFooterCss',
        'THEME_FOOTER_JS' => 'getFooterJs',
    ];

    private Filesystem $filesystem;
    private string $workspace;
    private string $themeDir;
    private string $themePublicDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workspace = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(uniqid('', true));
        $this->themeDir = $this->workspace . DIRECTORY_SEPARATOR . 'theme';
        $this->themePublicDir = $this->workspace . DIRECTORY_SEPARATOR . 'public-theme';

        $this->filesystem->mkdir($this->themeDir);
        $this->filesystem->mkdir($this->themePublicDir . DIRECTORY_SEPARATOR . 'custom');
        $this->filesystem->dumpFile($this->themePublicDir . '/custom/header.css', 'body { color: red; }');
        $this->filesystem->dumpFile($this->themePublicDir . '/custom/header.js', 'console.log("header");');
        $this->filesystem->dumpFile($this->themePublicDir . '/custom/footer.css', 'body { color: blue; }');
        $this->filesystem->dumpFile($this->themePublicDir . '/custom/footer.js', 'console.log("footer");');
        $this->filesystem->dumpFile($this->themePublicDir . '/custom/upper.CSS', 'body { color: red; }');
        $this->filesystem->dumpFile($this->themePublicDir . '/custom/upper.JS', 'console.log("upper");');
        $this->filesystem->dumpFile($this->workspace . '/outside.css', 'body { color: black; }');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workspace);
    }

    public function testConfiguredFilesAreResolvedToThemeAssetPaths(): void
    {
        $service = $this->createThemeService(
            [
                'THEME_HEADER_CSS' => 'custom/header.css',
                'THEME_HEADER_JS' => 'custom/header.js',
                'THEME_FOOTER_CSS' => 'custom/footer.css',
                'THEME_FOOTER_JS' => 'custom/footer.js',
            ]
        );

        self::assertSame('theme/custom/header.css', $service->getHeaderCss());
        self::assertSame('theme/custom/header.js', $service->getHeaderJs());
        self::assertSame('theme/custom/footer.css', $service->getFooterCss());
        self::assertSame('theme/custom/footer.js', $service->getFooterJs());
    }

    public function testEmptyAndUnconfiguredValuesResolveToNull(): void
    {
        $service = $this->createThemeService(
            [
                'THEME_HEADER_CSS' => '',
                'THEME_HEADER_JS' => '   ',
            ]
        );

        self::assertNull($service->getHeaderCss());
        self::assertNull($service->getHeaderJs());
        self::assertNull($service->getFooterCss());
        self::assertNull($service->getFooterJs());
    }

    public function testLeadingSlashIsAccepted(): void
    {
        $service = $this->createThemeService(['THEME_HEADER_CSS' => '/custom/header.css']);

        self::assertSame('theme/custom/header.css', $service->getHeaderCss());
    }

    public function testExtensionCheckIsCaseInsensitive(): void
    {
        $service = $this->createThemeService(
            [
                'THEME_HEADER_CSS' => 'custom/upper.CSS',
                'THEME_HEADER_JS' => 'custom/upper.JS',
            ]
        );

        self::assertSame('theme/custom/upper.CSS', $service->getHeaderCss());
        self::assertSame('theme/custom/upper.JS', $service->getHeaderJs());
    }

    /**
     * @param non-empty-string $setting
     * @param non-empty-string $configuredPath
     * @param non-empty-string $expectedMessage
     *
     * @dataProvider provideWrongExtensions
     */
    public function testWrongExtensionIsRejectedForEverySetting(
        string $setting,
        string $configuredPath,
        string $expectedMessage
    ): void {
        $service = $this->createThemeService([$setting => $configuredPath]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $getter = self::GETTERS[$setting];
        $service->{$getter}();
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function provideWrongExtensions(): array
    {
        return [
            'header css configured with a js file' => [
                'THEME_HEADER_CSS',
                'custom/header.js',
                'THEME_HEADER_CSS must reference a .css file, "custom/header.js" given.',
            ],
            'header js configured with a css file' => [
                'THEME_HEADER_JS',
                'custom/header.css',
                'THEME_HEADER_JS must reference a .js file, "custom/header.css" given.',
            ],
            'footer css configured with a js file' => [
                'THEME_FOOTER_CSS',
                'custom/footer.js',
                'THEME_FOOTER_CSS must reference a .css file, "custom/footer.js" given.',
            ],
            'footer js configured with a css file' => [
                'THEME_FOOTER_JS',
                'custom/footer.css',
                'THEME_FOOTER_JS must reference a .js file, "custom/footer.css" given.',
            ],
        ];
    }

    public function testNonStringValueIsRejected(): void
    {
        $service = $this->createThemeService(['THEME_HEADER_CSS' => 'true']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('THEME_HEADER_CSS must reference a .css file, bool given.');

        $service->getHeaderCss();
    }

    public function testFileWithoutExtensionIsRejected(): void
    {
        $service = $this->createThemeService(['THEME_FOOTER_CSS' => 'custom/header']);

        $this->expectException(InvalidArgumentException::class);

        $service->getFooterCss();
    }

    public function testMissingFileIsRejected(): void
    {
        $service = $this->createThemeService(['THEME_FOOTER_JS' => 'custom/missing.js']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('THEME_FOOTER_JS references "custom/missing.js", which does not exist');

        $service->getFooterJs();
    }

    public function testPathOutsideThemeDirectoryIsRejected(): void
    {
        $service = $this->createThemeService(['THEME_HEADER_CSS' => '../outside.css']);

        $this->expectException(InvalidArgumentException::class);

        $service->getHeaderCss();
    }

    /**
     * @param array<string, string> $settings
     */
    private function createThemeService(array $settings): ThemeService
    {
        return new ThemeService(
            $this->createStub(CacheItemPoolInterface::class),
            $this->createStub(CheckSignature::class),
            new RequestStack(),
            new ParameterBag($settings),
            new NullLogger(),
            $this->createStub(TranslatorInterface::class),
            $this->themeDir,
            $this->themePublicDir
        );
    }
}
