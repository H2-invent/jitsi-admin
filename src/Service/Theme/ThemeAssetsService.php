<?php

declare(strict_types=1);

namespace App\Service\Theme;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resolves the optional theme assets configured via THEME_HEADER_* / THEME_FOOTER_*.
 *
 * Configured values are paths relative to the theme asset directory (public/theme),
 * e.g. "custom/header.css" resolves to public/theme/custom/header.css and is exposed
 * as "theme/custom/header.css" so the templates can run it through asset().
 */
class ThemeAssetsService
{
    public function __construct(
        #[Autowire(param: 'app.theme.public_dir')]
        private readonly string $themePublicDir,
        #[Autowire(env: 'THEME_HEADER_CSS')]
        private readonly ?string $headerCss = null,
        #[Autowire(env: 'THEME_HEADER_JS')]
        private readonly ?string $headerJs = null,
        #[Autowire(env: 'THEME_FOOTER_CSS')]
        private readonly ?string $footerCss = null,
        #[Autowire(env: 'THEME_FOOTER_JS')]
        private readonly ?string $footerJs = null,
    ) {
    }

    public function getHeaderCss(): ?string
    {
        return $this->resolve($this->headerCss, 'css', 'THEME_HEADER_CSS');
    }

    public function getHeaderJs(): ?string
    {
        return $this->resolve($this->headerJs, 'js', 'THEME_HEADER_JS');
    }

    public function getFooterCss(): ?string
    {
        return $this->resolve($this->footerCss, 'css', 'THEME_FOOTER_CSS');
    }

    public function getFooterJs(): ?string
    {
        return $this->resolve($this->footerJs, 'js', 'THEME_FOOTER_JS');
    }

    private function resolve(?string $configuredPath, string $extension, string $variableName): ?string
    {
        $path = trim((string)$configuredPath);
        if ($path === '') {
            return null;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== $extension) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s must reference a .%s file, "%s" given.',
                    $variableName,
                    $extension,
                    $path
                )
            );
        }

        $relativePath = $this->resolveRelativePath($path);
        if ($relativePath === null) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s references "%s", which does not exist or is not readable in %s.',
                    $variableName,
                    $path,
                    $this->themePublicDir
                )
            );
        }

        return 'theme/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }

    private function resolveRelativePath(string $path): ?string
    {
        $themeDir = realpath($this->themePublicDir);
        if ($themeDir === false) {
            return null;
        }

        $absolutePath = realpath($themeDir . DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
        if ($absolutePath === false || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        if (!str_starts_with($absolutePath, $themeDir . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return substr($absolutePath, strlen($themeDir) + 1);
    }
}
