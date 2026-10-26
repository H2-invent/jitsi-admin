<?php

namespace App\Service\Theme;

use App\Entity\Rooms;
use H2Entwicklung\Signature\CheckSignature;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ThemeService
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly CheckSignature         $checkSignature,
        private readonly RequestStack           $request,
        private readonly ParameterBagInterface  $parameterBag,
        private readonly LoggerInterface        $logger,
        private readonly TranslatorInterface    $translator,
        #[Autowire(param: 'app.theme.dir')]
        private readonly string                 $themeDir,
        #[Autowire(param: 'app.theme.public_dir')]
        private readonly string                 $themePublicDir,
    )
    {
    }

    public function getTheme(?Rooms $room = null): mixed
    {
        if ($room) {
            if ($room->getHostUrl()) {
                // Hole die Host-URL aus dem Raumobjekt und entferne das Protokoll (https:// oder http://)
                $url = str_replace('https://', '', $room->getHostUrl());
                $url = str_replace('http://', '', $url);

                // Teile die URL anhand des Doppelpunkts (:), um den Port zu entfernen, falls vorhanden
                $urlParts = explode(':', $url);
                if (count($urlParts) > 1) {
                    // Falls ein Port vorhanden ist, nutze nur den Hostnamen (den Teil vor dem Doppelpunkt)
                    $url = $urlParts[0];
                }
            } else {
                // Falls keine Host-URL vorhanden ist, gib false zurück
                return false;
            }
        } else {
            if ($this->request->getCurrentRequest()) {
                // Hole die Host-URL aus der aktuellen Anfrage
                $url = $this->request->getCurrentRequest()->getHost();

                // Teile die URL anhand des Doppelpunkts (:), um den Port zu entfernen, falls vorhanden
                $urlParts = explode(':', $url);
                if (count($urlParts) > 1) {
                    // Falls ein Port vorhanden ist, nutze nur den Hostnamen (den Teil vor dem Doppelpunkt)
                    $url = $urlParts[0];
                }
            } else {
                // Falls keine Anfrage vorhanden ist, gib false zurück
                return false;
            }
        }


        try {
            $item = $this->cache->getItem('theme_' . $url);
            if ($item->isHit()) {
                return $item->get();
            }

            $item->expiresAfter(3600);
            $value = false;

            $finder = new Finder();
            $finder->files()->in($this->themeDir)->name($url . '.' . 'theme.json.signed');
            if ($finder->count() > 0) {
                $arr = iterator_to_array($finder);
                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                $file  = reset($arr);
                $theme = $file->getContents();

                $valid = $this->checkSignature->verifySignature($theme);
                if ($valid) {
                    $res = $this->checkSignature->verifyValidUntil($theme);
                    if ($res !== false) {
                        $value = $res;
                    } else {
                        $this->logger->error('Theme valid until is before now');
                    }
                } else {
                    $this->logger->error('Signature invalid');
                }
            }

            $item->set($value);
            $this->cache->save($item);

            return $value;
        } catch (\Exception) {
        }

        return false;
    }

    public function getThemeProperty(string $property): mixed
    {
        $theme = $this->getTheme();
        if ($theme) {
            return $theme[$property] ?? null;
        }

        return null;
    }

    public function getApplicationProperties(string $input): mixed
    {
        $variable = null;
        if ($this->parameterBag->has($input)) {
            /** @var string $variable */
            $variable = $this->parameterBag->get($input);
        }

        $tmp = $this->getThemeProperty($input);

        if ($tmp !== null) {
            $res = json_decode($tmp, true);
            if ($res === null) {
                return $tmp;
            }
            if ($res === false) {
                return $res;
            }
            return $res;
        }

        $res = null;
        if ($variable) {
            $res = json_decode($variable, true);
        }

        if ($res === null) {
            return $variable;
        }

        if ($res === false) {
            return $res;
        }

        return $res;
    }

    /**
     * The theme asset getters return a path relative to public/ (e.g. "theme/custom/x.css"),
     * ready to be passed to asset(), or null when the setting is not configured.
     */
    public function getHeaderCss(): ?string
    {
        return $this->resolveThemeAsset('THEME_HEADER_CSS', 'css');
    }

    public function getHeaderJs(): ?string
    {
        return $this->resolveThemeAsset('THEME_HEADER_JS', 'js');
    }

    public function getFooterCss(): ?string
    {
        return $this->resolveThemeAsset('THEME_FOOTER_CSS', 'css');
    }

    public function getFooterJs(): ?string
    {
        return $this->resolveThemeAsset('THEME_FOOTER_JS', 'js');
    }

    private function resolveThemeAsset(string $setting, string $extension): ?string
    {
        $configuredPath = $this->getApplicationProperties($setting);
        if ($configuredPath === null || $configuredPath === '') {
            return null;
        }

        if (!is_string($configuredPath)) {
            throw new InvalidArgumentException(
                sprintf('%s must reference a .%s file, %s given.', $setting, $extension, get_debug_type($configuredPath))
            );
        }

        $path = trim($configuredPath);
        if ($path === '') {
            return null;
        }

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== $extension) {
            throw new InvalidArgumentException(
                sprintf('%s must reference a .%s file, "%s" given.', $setting, $extension, $path)
            );
        }

        $relativePath = $this->resolveThemeAssetRelativePath($path);
        if ($relativePath === null) {
            throw new InvalidArgumentException(
                sprintf('%s references "%s", which does not exist or is not readable in %s.', $setting, $path, $this->themePublicDir)
            );
        }

        return 'theme/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    }

    private function resolveThemeAssetRelativePath(string $path): ?string
    {
        $themePublicDir = realpath($this->themePublicDir);
        if ($themePublicDir === false) {
            return null;
        }

        $absolutePath = realpath($themePublicDir . DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
        if ($absolutePath === false || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        if (!str_starts_with($absolutePath, $themePublicDir . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return substr($absolutePath, strlen($themePublicDir) + 1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllThemes(): array
    {
        $finder = new Finder()
            ->files()
            ->in($this->themeDir)
            ->name('*.json.signed')
            ->sortByName();

        $themes = [];

        foreach ($finder as $file) {
            $raw = $file->getContents();

            $data = \json_decode($raw, true);
            if (!\is_array($data)) {
                // kaputte Datei -> trotzdem listen, aber markieren
                $themes[] = [
                    'filename'     => $file->getFilename(),
                    'title'        => null,
                    'validUntil'   => null,
                    'validUntilTs' => null,
                    'modified'     => new \DateTimeImmutable()->setTimestamp($file->getMTime()),
                    'size'         => $file->getSize(),
                    'error'        => 'Invalid JSON',
                ];
                continue;
            }

            $validUntilStr = $data['entry']['validUntil'] ?? null;

            // robust: validUntil kann fehlen oder Müll sein
            $validUntil   = null;
            $validUntilTs = null;
            if (\is_string($validUntilStr) && $validUntilStr !== '') {
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $validUntilStr) ?: null;
                if ($dt) {
                    $validUntil   = $dt;
                    $validUntilTs = $dt->getTimestamp();
                }
            }

            $themes[] = [
                'filename'      => $file->getFilename(),
                'title'         => $data['entry']['title'] ?? null,
                'primaryColor'  => $data['entry']['primaryColor'] ?? null,
                'signature'     => $data['signature'] ?? null,
                'validUntil'    => $validUntil,     // DateTimeImmutable|null
                'validUntilRaw' => $validUntilStr,  // string|null (falls Format kaputt)
                'validUntilTs'  => $validUntilTs,   // int|null (zum Sortieren)
                'modified'      => new \DateTimeImmutable()->setTimestamp($file->getMTime()),
                'size'          => $file->getSize(),
                'error'         => null,
            ];
        }

        // Optional: nach validUntil sortieren (frühestes zuerst), dann filename
        usort($themes, static function (array $a, array $b): int {
            $at = $a['validUntilTs'] ?? PHP_INT_MAX;
            $bt = $b['validUntilTs'] ?? PHP_INT_MAX;
            if ($at === $bt) {
                return ($a['filename'] <=> $b['filename']);
            }
            return $at <=> $bt;
        });

        return $themes;
    }

    public function checkRemainingDays(): ?int
    {
        $validUntil = $this->getThemeProperty('validUntil');
        if ($validUntil) {
            $validDate = new \DateTimeImmutable($validUntil);
            $now       = new \DateTimeImmutable();
            $daysDifff = intval(($now->diff($validDate))->format('%R%a'));
            if ($daysDifff < $this->getApplicationProperties('SECURITY_THEME_REMINDER_DAYS')) {
                /** @var FlashBagInterface $flashBag */
                $flashBag = $this->request->getSession()->getBag('flashes');
                $flashBag->add(
                    $daysDifff > 0 ? 'warning' : 'danger',
                    $this->translator->trans('theme.invalid.', ['{days}' => $daysDifff])
                );
            }
            return $daysDifff;
        }

        return null;
    }

    /**
     * @return array<int, array<int, mixed>>|bool
     */
    public function showAllThemes(): bool|array
    {
        /** @var string $projectDir */
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $finder     = new Finder();
        $finder->files()->in($projectDir . '/theme/')->name('*.theme.json.signed');
        if (!$finder->hasResults()) {
            return false;
        }

        $res = [];
        $arr = iterator_to_array($finder);

        foreach ($arr as $file) {
            $theme = $file->getContents();

            $tmp   = [
                $file->getFilename(),
            ];
            $tmp[] = json_decode($theme, true)['entry']['validUntil'];
            $res[] = $tmp;
        }

        return $res;
    }
}
