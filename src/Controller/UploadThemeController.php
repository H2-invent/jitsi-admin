<?php

namespace App\Controller;

use App\Form\Type\ThemeUploadType;
use App\Service\ThemeService;
use H2Entwicklung\Signature\CheckSignature;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/room/upload/theme/', name: 'app_upload_theme_')]
class UploadThemeController extends AbstractController
{
    public function __construct(
        private UrlGeneratorInterface  $urlGenerator,
        private ParameterBagInterface  $parameterBag,
        private CheckSignature         $checkSignature,
        private CacheItemPoolInterface $cacheItemPool,
        private ThemeService           $themeService,
        readonly private string  $themeDir, // z.B. per Parameter/DI setzen
    )
    {
        $this->CACHE_DIR = $this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->THEME_DIR = $this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'theme' . DIRECTORY_SEPARATOR;
        $this->PUBLIC_DIR = $this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR.'theme'.DIRECTORY_SEPARATOR;
    }

    private $THEME_DIR;
    private $PUBLIC_DIR;
    private $CACHE_DIR;

    #[Route('form', name: 'form', methods: ['GET'])]
    public function index(): Response
    {
        if ($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP') !== '') {
            $groups = $this->getUser()->getGroups();
            if (!$groups || !in_array($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP'), $groups)) {
                $this->addFlash('danger', 'Permission denied');
                return $this->redirectToRoute('index');
            }
        }
        $form = $this->createForm(ThemeUploadType::class, null, ['action' => $this->urlGenerator->generate('app_upload_theme_save')]);
        return $this->render('upload_theme/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('showThemes', name: 'showThemes', methods: ['GET'])]
    public function showThemes(): Response
    {
        if ($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP') !== '') {
            $groups = $this->getUser()->getGroups();
            if (!$groups || !in_array($this->themeService->getApplicationProperties('SECURITY_ALLLOW_UPLOAD_THEME_GROUP'), $groups)) {
                $this->addFlash('danger', 'Permission denied');
                return $this->redirectToRoute('index');
            }
        }

        $finder = (new Finder())
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
                    'filename'   => $file->getFilename(),
                    'title'      => null,
                    'validUntil' => null,
                    'validUntilTs' => null,
                    'modified'   => (new \DateTimeImmutable())->setTimestamp($file->getMTime()),
                    'size'       => $file->getSize(),
                    'error'      => 'Invalid JSON',
                ];
                continue;
            }

            $validUntilStr = $data['entry']['validUntil'] ?? null;

            // robust: validUntil kann fehlen oder Müll sein
            $validUntil = null;
            $validUntilTs = null;
            if (\is_string($validUntilStr) && $validUntilStr !== '') {
                $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $validUntilStr) ?: null;
                if ($dt) {
                    $validUntil = $dt;
                    $validUntilTs = $dt->getTimestamp();
                }
            }

            $themes[] = [
                'filename'     => $file->getFilename(),
                'title'        => $data['entry']['title'] ?? null,
                'primaryColor' => $data['entry']['primaryColor'] ?? null,
                'signature' => $data['signature']?? null,
                'validUntil'   => $validUntil,     // DateTimeImmutable|null
                'validUntilRaw'=> $validUntilStr,  // string|null (falls Format kaputt)
                'validUntilTs' => $validUntilTs,   // int|null (zum Sortieren)
                'modified'     => (new \DateTimeImmutable())->setTimestamp($file->getMTime()),
                'size'         => $file->getSize(),
                'error'        => null,
            ];
        }

        // Optional: nach validUntil sortieren (frühestes zuerst), dann filename
        usort($themes, static function(array $a, array $b): int {
            $at = $a['validUntilTs'] ?? PHP_INT_MAX;
            $bt = $b['validUntilTs'] ?? PHP_INT_MAX;
            if ($at === $bt) {
                return ($a['filename'] <=> $b['filename']);
            }
            return $at <=> $bt;
        });

        return $this->render('upload_theme/themeOverview.html.twig', [
            'themes' => $themes,
            'now' => new \DateTimeImmutable('today'),
        ]);
    }


    #[Route('save', name: 'save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $form = $this->createForm(ThemeUploadType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $themeFile */
            $themeFile = $form->get('theme')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($themeFile) {
                try {

                    $path = $this->CACHE_DIR . md5(uniqid());
                    $zip = new \ZipArchive();
                    $res = $zip->open($themeFile->getRealPath());
                    if ($res) {
                        $zip->extractTo($path);
                        $zip->close();

                        $finder = new Finder();
                        $finder->files()->in($path)->name('*.json.signed');
                        if ($finder->count() === 1) {
                            $arr = iterator_to_array($finder);
                            $themePath = reset($arr);
                            $theme = $themePath->getContents();
                            $validSignature = $this->checkSignature->verifySignature($theme);
                            if (!$validSignature) {
                                $this->addFlash('danger', 'Theme is invalid');
                                return $this->redirectToRoute('app_upload_theme_form');
                            }
                            $this->moveTheme(reset($arr), $path);
                            $filesystem = new Filesystem();
                            $filesystem->remove($path);
                            $this->cacheItemPool->clear();
                            $this->addFlash('success', 'Theme successfully uploaded');
                            return $this->redirectToRoute('app_upload_theme_form');
                        } else {
                            $this->addFlash('danger', 'No Theme in the zip');
                            return $this->redirectToRoute('app_upload_theme_form');
                        }

                    }
                } catch (\Exception $exception) {
                    $this->addFlash('danger', $exception->getMessage());
                    return $this->redirectToRoute('app_upload_theme_form');
                }
            } else {
                $this->addFlash('danger', 'No Theme uploaded');
                return $this->redirectToRoute('app_upload_theme_form');
            }
        }
        $this->addFlash('danger', 'Please upload a zip file');
        return $this->redirectToRoute('app_upload_theme_form');
    }

    private function processTheme(string $themeFilePath): void
    {
        $path = $this->CACHE_DIR . md5(uniqid());
        $zip = new \ZipArchive();

        if ($zip->open($themeFilePath) !== true) {
            throw new \RuntimeException('Unable to open the zip file');
        }

        $zip->extractTo($path);
        $zip->close();

        $finder = new Finder();
        $finder->files()->in($path)->name('*.json.signed');

        if ($finder->count() !== 1) {
            throw new \RuntimeException('No valid theme file found in the zip');
        }

        $items = iterator_to_array($finder);
        $themePath = reset($items)->getRealPath();

        if (!$themePath) {
            throw new \RuntimeException('Unable to read theme file');
        }

        $themeContent = file_get_contents($themePath);
        $validSignature = $this->checkSignature->verifySignature($themeContent);

        if (!$validSignature) {
            throw new \RuntimeException('Invalid theme signature');
        }

        $this->moveTheme($themePath, $path);

        $filesystem = new Filesystem();
        $filesystem->remove($path);

        $this->cacheItemPool->clear();
    }

    private function moveTheme($themePath, $path)
    {
        $filesystem = new Filesystem();
        $tmp = explode(DIRECTORY_SEPARATOR, $themePath);
        $fileName = end($tmp);
        $themeTargetPath = $this->THEME_DIR . $fileName;
        $filesystem->remove($themeTargetPath);
        $filesystem->copy($themePath, $themeTargetPath);
        $filesystem->remove($themePath);

        $finder = new Finder();
        $finder->depth('==0');
        $finder->files()->in($path)->directories();
        $arr = iterator_to_array($finder);
        foreach ($arr as $assest) {
            $tmp = explode(DIRECTORY_SEPARATOR, $assest);
            $dir = end($tmp);
            if (str_starts_with($dir,'theme')){
                $dir = preg_replace('/theme/','',$dir);
            }
            $assetTargetPath = $this->PUBLIC_DIR . $dir;
            $filesystem->remove($assetTargetPath . '/*');
            $filesystem->mirror($assest, $assetTargetPath);
        }

    }
}
