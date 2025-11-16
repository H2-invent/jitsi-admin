<?php
declare(strict_types=1);

namespace App\Tests\Theme;

use App\Service\Result\Error\ThemeUploadError;
use App\Service\Theme\ThemeUploadService;
use H2Entwicklung\Signature\CheckSignature;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ThemeUploadServiceTest extends TestCase
{
    private Filesystem $filesystem;
    private string $workspace;
    private int $umask;

    private string $pathValidTheme = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'validtheme.zip';
    private string $pathInvalidZip = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'invalidzip.zip';
    private string $pathNoSignatureFile = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'nosignaturefile.zip';

    protected function setUp(): void
    {
        $this->umask = umask(0);
        $this->filesystem = new Filesystem();
        $this->workspace = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(uniqid());
        mkdir($this->workspace, 0777, true);
        $this->workspace = realpath($this->workspace);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workspace);
        umask($this->umask);
    }

    public function testUploadValidTheme(): void
    {
        $mockCheckSignature = $this->createMock(CheckSignature::class);
        $mockCheckSignature->expects($this->once())->method('verifySignature')->willReturn(true);
        $mockCacheItemPool = $this->createStub(CacheItemPoolInterface::class);

        $themeUploadService = new ThemeUploadService($mockCheckSignature, $mockCacheItemPool, $this->workspace);
        $uploadThemeResult = $themeUploadService->uploadTheme($this->pathValidTheme);

        [$themeDir, $publicDir] = $this->getThemeDirAndPublicDirFromService($themeUploadService);
        $themeFinder = (new Finder())->files()->in($themeDir)->name('*.json.signed');
        $publicFinder = (new Finder())->files()->in($publicDir)->name('filefromzip.test');

        self::assertTrue($uploadThemeResult->isSuccess());
        self::assertSame(1, $themeFinder->count());
        self::assertSame(1, $publicFinder->count());
    }

    public function testUploadInvalidZip(): void
    {
        $mockCheckSignature = $this->createStub(CheckSignature::class);
        $mockCheckSignature->method('verifySignature')->willReturn(true);
        $mockCacheItemPool = $this->createStub(CacheItemPoolInterface::class);

        $themeUploadService = new ThemeUploadService($mockCheckSignature, $mockCacheItemPool, $this->workspace);
        $uploadThemeResult = $themeUploadService->uploadTheme($this->pathInvalidZip);

        self::assertFalse($uploadThemeResult->isSuccess());
        self::assertSame(ThemeUploadError::INVALID_ZIP, $uploadThemeResult->getErrorType());
    }

    public function testUploadNoSignatureFile(): void
    {
        $mockCheckSignature = $this->createStub(CheckSignature::class);
        $mockCheckSignature->method('verifySignature')->willReturn(true);
        $mockCacheItemPool = $this->createStub(CacheItemPoolInterface::class);

        $themeUploadService = new ThemeUploadService($mockCheckSignature, $mockCacheItemPool, $this->workspace);
        $uploadThemeResult = $themeUploadService->uploadTheme($this->pathNoSignatureFile);

        self::assertFalse($uploadThemeResult->isSuccess());
        self::assertSame(ThemeUploadError::NO_THEME_IN_ZIP, $uploadThemeResult->getErrorType());
    }

    public function testUploadInvalidSignature(): void
    {
        $mockCheckSignature = $this->createStub(CheckSignature::class);
        $mockCheckSignature->method('verifySignature')->willReturn(false);
        $mockCacheItemPool = $this->createStub(CacheItemPoolInterface::class);

        $themeUploadService = new ThemeUploadService($mockCheckSignature, $mockCacheItemPool, $this->workspace);
        $uploadThemeResult = $themeUploadService->uploadTheme($this->pathValidTheme);

        self::assertFalse($uploadThemeResult->isSuccess());
        self::assertSame(ThemeUploadError::INVALID_THEME, $uploadThemeResult->getErrorType());
    }

    /**
     * @return string[]
     */
    private function getThemeDirAndPublicDirFromService(ThemeUploadService $themeUploadService): array
    {
        $reflectionService = new \ReflectionClass($themeUploadService);
        $themeDir = $reflectionService->getProperty('themeDir')->getValue($themeUploadService);
        $publicDir = $reflectionService->getProperty('publicDir')->getValue($themeUploadService);

        return [$themeDir, $publicDir];
    }
}
