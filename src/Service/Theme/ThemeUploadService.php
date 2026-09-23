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
    public function __construct(
        private CheckSignature $checkSignature,
        private CacheItemPoolInterface $cacheItemPool,
        private Filesystem $filesystem,
        #[Autowire(param: 'app.theme.dir')]
        private readonly string $themeDir,
        #[Autowire(param: 'app.theme.cache_dir')]
        private readonly string $cacheDir,
        #[Autowire(param: 'app.theme.public_dir')]
        private readonly string $publicDir,
    )
    {
    }

    public function uploadTheme(string $absoluteFilePathZip): ServiceResult
    {
        $extractionPath = $this->cacheDir . DIRECTORY_SEPARATOR . md5(uniqid());
        $success = $this->extractZipToPath($absoluteFilePathZip, $extractionPath);
        if (!$success) {
            return ServiceResult::failure(ThemeUploadError::INVALID_ZIP);
        }

        $signatureFile = $this->findSignatureFile($extractionPath);
        $themeDirectory = $this->findThemeDirectory($extractionPath);
        if ($signatureFile === null || $themeDirectory === null) {
            return ServiceResult::failure(ThemeUploadError::NO_THEME_IN_ZIP);
        }

        $signatureFileContent = $signatureFile->getContents();
        $validSignature = $this->checkSignature->verifySignature($signatureFileContent);
        if (!$validSignature) {
            return ServiceResult::failure(ThemeUploadError::INVALID_THEME);
        }

        $this->moveSignatureFile($signatureFile);
        $this->moveThemeFiles($themeDirectory);
        $this->removeExtractedFiles($extractionPath);

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
        $finder = (new Finder())
            ->files()
            ->name('*.json.signed')
            ->in($extractionPath)
        ;
        if ($finder->count() !== 1) {
            return null;
        }
        $foundFiles = iterator_to_array($finder, false);

        return $foundFiles[0] ?? null;
    }

    private function findThemeDirectory(string $extractionPath): ?SplFileInfo
    {
        $finder = (new Finder())
            ->directories()
            ->name('theme')
            ->in($extractionPath)
        ;
        if ($finder->count() !== 1) {
            return null;
        }
        $foundFiles = iterator_to_array($finder, false);

        return $foundFiles[0] ?? null;
    }

    private function moveSignatureFile(SplFileInfo $signatureFile): void
    {
        $signaturePath = $signatureFile->getPathname();
        $signatureTargetPath = $this->themeDir . DIRECTORY_SEPARATOR . $signatureFile->getFilename();

        $this->filesystem->copy($signaturePath, $signatureTargetPath, true);
        $this->filesystem->remove($signaturePath);
    }

    private function moveThemeFiles(SplFileInfo $themeDirectory): void
    {
        $finder = (new Finder())
            ->in($themeDirectory->getPathname())
            ->depth(0)
        ;
        foreach ($finder as $fileOrDir) {
            $sourcePathName = $fileOrDir->getPathname();
            $targetPathName = $this->publicDir . DIRECTORY_SEPARATOR . $fileOrDir->getFilename();

            if ($fileOrDir->isFile()) {
                $this->filesystem->copy($sourcePathName, $targetPathName, true);

            } elseif ($fileOrDir->isDir()) {
                $this->filesystem->mirror($sourcePathName, $targetPathName, options: [
                    'override' => true,
                    'delete' => true,
                ]);
            }
        }
    }

    private function removeExtractedFiles(string $extractionPath): void
    {
        $this->filesystem->remove($extractionPath);
        $this->cacheItemPool->clear();
    }
}
