<?php
declare(strict_types=1);

namespace App\Service\Theme;

use App\Service\Result\Error\ThemeUploadError;
use App\Service\Result\ServiceResult;
use H2Entwicklung\Signature\CheckSignature;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ThemeUploadService
{
    private string $cacheDir;
    private string $themeDir;
    private string $publicDir;

    public function __construct(
        private CheckSignature         $checkSignature,
        private CacheItemPoolInterface $cacheItemPool,
        #[Autowire(param: 'kernel.project_dir')]
        string $kernelProjektDir,
    )
    {
        $this->cacheDir = $kernelProjektDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->themeDir = $kernelProjektDir . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->publicDir = $kernelProjektDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
    }

    public function uploadTheme(string $absoluteFilePathZip): ServiceResult
    {
        $extractionPath = $this->cacheDir . md5(uniqid());
        $success = $this->extractZipToPath($absoluteFilePathZip, $extractionPath);
        if (!$success) {
            return ServiceResult::failure(ThemeUploadError::INVALID_ZIP);
        }

        $signatureFile = $this->findSignatureFile($extractionPath);
        if ($signatureFile === null) {
            return ServiceResult::failure(ThemeUploadError::NO_THEME_IN_ZIP);
        }

        $signatureFileContent = $signatureFile->getContents();
        $validSignature = $this->checkSignature->verifySignature($signatureFileContent);
        if (!$validSignature) {
            return ServiceResult::failure(ThemeUploadError::INVALID_THEME);
        }

        $themePath = $signatureFile->getPathname();
        $themeTargetPath = $this->themeDir . $signatureFile->getFilename();
        $this->moveThemeToTargetPathAndRemoveTempFiles($themePath, $themeTargetPath, $extractionPath);

        return ServiceResult::success();
    }

    private function extractZipToPath(string $absoluteFilePathZip, string $path): bool
    {
        $zip = new \ZipArchive();
        $zipResult = $zip->open($absoluteFilePathZip);
        // $zipResult is either true or an int, which also evaluates truthy, so we have to check for bool like this
        if ($zipResult !== true) {
            return false;
        }
        $zip->extractTo($path);
        $zip->close();

        return true;
    }

    private function findSignatureFile(string $extractionPath): ?SplFileInfo
    {
        $finder = new Finder();
        $finder->files()->in($extractionPath)->name('*.json.signed');
        if ($finder->count() !== 1) {
            return null;
        }
        $foundFiles = iterator_to_array($finder, false);

        return $foundFiles[0] ?? null;
    }

    private function moveThemeToTargetPathAndRemoveTempFiles(string $themePath, string $themeTargetPath, string $extractionPath): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove($themeTargetPath);
        $filesystem->copy($themePath, $themeTargetPath);
        $filesystem->remove($themePath);

        $finder = new Finder();
        $finder->depth('==0');
        $finder->files()->in($extractionPath)->directories();

        foreach ($finder as $assetDir) {
            $dirName = $assetDir->getFilename();
            if (str_starts_with($dirName, 'theme')) {
                $dirName = str_replace("theme", '', $dirName);
            }
            $assetTargetPath = $this->publicDir . $dirName;
            $filesystem->remove($assetTargetPath . '/*');
            $filesystem->mirror($assetDir->getPath(), $assetTargetPath);
        }

        $filesystem->remove($extractionPath);
        $this->cacheItemPool->clear();
    }
}