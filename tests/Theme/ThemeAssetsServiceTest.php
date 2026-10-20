<?php

declare(strict_types=1);

namespace App\Tests\Theme;

use App\Service\Theme\ThemeAssetsService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ThemeAssetsServiceTest extends TestCase
{
    private Filesystem $filesystem;
    private string $workspace;
    private string $themeDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workspace = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(uniqid('', true));
        $this->themeDir = $this->workspace . DIRECTORY_SEPARATOR . 'theme';

        $this->filesystem->mkdir($this->themeDir . DIRECTORY_SEPARATOR . 'custom');
        $this->filesystem->dumpFile($this->themeDir . '/custom/header.css', 'body { color: red; }');
        $this->filesystem->dumpFile($this->themeDir . '/custom/header.js', 'console.log("header");');
        $this->filesystem->dumpFile($this->themeDir . '/custom/footer.css', 'body { color: blue; }');
        $this->filesystem->dumpFile($this->themeDir . '/custom/footer.js', 'console.log("footer");');
        $this->filesystem->dumpFile($this->themeDir . '/custom/upper.CSS', 'body { color: red; }');
        $this->filesystem->dumpFile($this->themeDir . '/custom/upper.JS', 'console.log("upper");');
        $this->filesystem->dumpFile($this->workspace . '/outside.css', 'body { color: black; }');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workspace);
    }

    public function testConfiguredFilesAreResolvedToThemeAssetPaths(): void
    {
        $service = $this->createService(
            'custom/header.css',
            'custom/header.js',
            'custom/footer.css',
            'custom/footer.js'
        );

        self::assertSame('theme/custom/header.css', $service->getHeaderCss());
        self::assertSame('theme/custom/header.js', $service->getHeaderJs());
        self::assertSame('theme/custom/footer.css', $service->getFooterCss());
        self::assertSame('theme/custom/footer.js', $service->getFooterJs());
    }

    public function testEmptyAndUnconfiguredValuesResolveToNull(): void
    {
        $service = $this->createService('', '   ', null, null);

        self::assertNull($service->getHeaderCss());
        self::assertNull($service->getHeaderJs());
        self::assertNull($service->getFooterCss());
        self::assertNull($service->getFooterJs());
    }

    public function testLeadingSlashIsAccepted(): void
    {
        $service = $this->createService('/custom/header.css');

        self::assertSame('theme/custom/header.css', $service->getHeaderCss());
    }

    public function testExtensionCheckIsCaseInsensitive(): void
    {
        $service = $this->createService('custom/upper.CSS', 'custom/upper.JS');

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
        $service = $this->createServiceFor($setting, $configuredPath);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->resolve($service, $setting);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function provideWrongExtensions(): array
    {
        return [
            'header css configured with a js file' => [
                'headerCss',
                'custom/header.js',
                'THEME_HEADER_CSS must reference a .css file, "custom/header.js" given.',
            ],
            'header js configured with a css file' => [
                'headerJs',
                'custom/header.css',
                'THEME_HEADER_JS must reference a .js file, "custom/header.css" given.',
            ],
            'footer css configured with a js file' => [
                'footerCss',
                'custom/footer.js',
                'THEME_FOOTER_CSS must reference a .css file, "custom/footer.js" given.',
            ],
            'footer js configured with a css file' => [
                'footerJs',
                'custom/footer.css',
                'THEME_FOOTER_JS must reference a .js file, "custom/footer.css" given.',
            ],
        ];
    }

    public function testFileWithoutExtensionIsRejected(): void
    {
        $service = $this->createService(null, null, 'custom/header');

        $this->expectException(InvalidArgumentException::class);

        $service->getFooterCss();
    }

    public function testMissingFileIsRejected(): void
    {
        $service = $this->createService(null, null, null, 'custom/missing.js');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('THEME_FOOTER_JS references "custom/missing.js", which does not exist');

        $service->getFooterJs();
    }

    public function testPathOutsideThemeDirectoryIsRejected(): void
    {
        $service = $this->createService('../outside.css');

        $this->expectException(InvalidArgumentException::class);

        $service->getHeaderCss();
    }

    private function createService(
        ?string $headerCss = null,
        ?string $headerJs = null,
        ?string $footerCss = null,
        ?string $footerJs = null
    ): ThemeAssetsService {
        return new ThemeAssetsService($this->themeDir, $headerCss, $headerJs, $footerCss, $footerJs);
    }

    private function createServiceFor(string $setting, string $configuredPath): ThemeAssetsService
    {
        $values = ['headerCss' => null, 'headerJs' => null, 'footerCss' => null, 'footerJs' => null];
        $values[$setting] = $configuredPath;

        return $this->createService(
            $values['headerCss'],
            $values['headerJs'],
            $values['footerCss'],
            $values['footerJs']
        );
    }

    private function resolve(ThemeAssetsService $service, string $setting): ?string
    {
        return match ($setting) {
            'headerCss' => $service->getHeaderCss(),
            'headerJs' => $service->getHeaderJs(),
            'footerCss' => $service->getFooterCss(),
            'footerJs' => $service->getFooterJs(),
        };
    }
}
