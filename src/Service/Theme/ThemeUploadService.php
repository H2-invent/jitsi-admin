<?php
declare(strict_types=1);

namespace App\Service\Theme;

use App\Service\Result\Error\ThemeUploadError;
use App\Service\Result\ServiceResult;
use App\Twig\Theme;
use H2Entwicklung\Signature\CheckSignature;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ThemeUploadService
{
    private string $cacheDir;
    private string $themeDir;
    private string $publicDir;

    public function __construct(
        private ParameterBagInterface  $parameterBag,
        private CheckSignature         $checkSignature,
        private CacheItemPoolInterface $cacheItemPool,
    )
    {
        $this->cacheDir = $this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->themeDir = $this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->publicDir = $this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
    }

    public function uploadTheme(UploadedFile $themeFile): ServiceResult
    {
        $path = $this->cacheDir . md5(uniqid());
        $zip = new \ZipArchive();
        $res = $zip->open($themeFile->getRealPath());
        if (!$res) {
            return ServiceResult::failure(ThemeUploadError::INVALID_ZIP);
        }

        $zip->extractTo($path);
        $zip->close();

        $finder = new Finder();
        $finder->files()->in($path)->name('*.json.signed');
        if ($finder->count() !== 1) {
            return ServiceResult::failure(ThemeUploadError::NO_THEME_IN_ZIP);
        }

        $arr = iterator_to_array($finder);
        $themePath = reset($arr);
        $theme = $themePath->getContents();

        $validSignature = $this->checkSignature->verifySignature($theme);
        if (!$validSignature) {
            return ServiceResult::failure(ThemeUploadError::INVALID_THEME);
        }
        $this->moveTheme(reset($arr), $path);
        $filesystem = new Filesystem();
        $filesystem->remove($path);
        $this->cacheItemPool->clear();

        return ServiceResult::success();
    }

    private function moveTheme(\SplFileInfo $themeFile, $path)
    {
        $themePath = $themeFile->getPathname();
        $themeTargetPath = $this->themeDir . $themeFile->getFilename();

        $filesystem = new Filesystem();
        $filesystem->remove($themeTargetPath);
        $filesystem->copy($themePath, $themeTargetPath);
        $filesystem->remove($themePath);

        $finder = new Finder();
        $finder->depth('==0');
        $finder->files()->in($path)->directories();
        foreach ($finder as $asset) {
            $dir = $asset->getFilename();
            if (str_starts_with($dir, 'theme')) {
                $dir = str_replace("theme", '', $dir);
            }
            $assetTargetPath = $this->publicDir . $dir;
            $filesystem->remove($assetTargetPath . '/*');
            $filesystem->mirror($asset->getPath(), $assetTargetPath);
        }
    }
}