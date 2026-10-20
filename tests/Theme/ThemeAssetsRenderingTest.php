<?php

declare(strict_types=1);

namespace App\Tests\Theme;

use App\Service\Theme\ThemeAssetsService;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Context\RequestStackContext;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Renders the real theme layout templates to verify that the configured assets
 * end up in the head resp. the footer section.
 */
class ThemeAssetsRenderingTest extends TestCase
{
    private const TEMPLATE_DIR = __DIR__ . '/../../templates';

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
        $this->filesystem->dumpFile($this->themeDir . '/custom/upper.JS', 'console.log("upper");');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workspace);
    }

    public function testHeaderAssetsAreRenderedInsideHead(): void
    {
        $twig = $this->createTwig($this->createService('custom/header.css', 'custom/header.js'));

        $html = $twig->render('base/__head.html.twig', ['theme' => false]);

        $document = new DOMDocument();
        $document->loadHTML($html);
        $xpath = new DOMXPath($document);

        self::assertSame(
            1,
            $xpath->query('//head/link[@rel="stylesheet"][@href="/theme/custom/header.css"]')->count()
        );
        self::assertSame(
            1,
            $xpath->query('//head/script[@src="/theme/custom/header.js"]')->count()
        );
        self::assertSame(0, $xpath->query('//script[contains(@src, "footer")]')->count());
    }

    public function testFooterAssetsAreRenderedAfterTheApplicationScripts(): void
    {
        $twig = $this->createTwig(
            $this->createService(null, null, 'custom/footer.css', 'custom/footer.js')
        );

        $html = $twig->render('base/__script.html.twig');

        self::assertStringContainsString('<link rel="stylesheet" href="/theme/custom/footer.css">', $html);
        self::assertStringContainsString('<script src="/theme/custom/footer.js"></script>', $html);
        self::assertStringNotContainsString('header.', $html);
        $appScriptPosition = strpos($html, '/build/app.js');
        $footerScriptPosition = strpos($html, '/theme/custom/footer.js');
        self::assertIsInt($appScriptPosition);
        self::assertIsInt($footerScriptPosition);
        self::assertLessThan($footerScriptPosition, $appScriptPosition);
    }

    public function testNothingIsRenderedWhenNoAssetsAreConfigured(): void
    {
        $twig = $this->createTwig($this->createService());

        self::assertSame('', trim($twig->render('base/__theme_assets_head.html.twig')));
        self::assertSame('', trim($twig->render('base/__theme_assets_footer.html.twig')));
    }

    public function testJavaScriptFileConfiguredAsCssBreaksRendering(): void
    {
        $twig = $this->createTwig($this->createService('custom/header.js'));

        try {
            $twig->render('base/__head.html.twig', ['theme' => false]);
            self::fail('Rendering must fail for an invalid theme asset configuration.');
        } catch (RuntimeError $exception) {
            self::assertStringContainsString('THEME_HEADER_CSS must reference a .css file', $exception->getMessage());
            self::assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
        }
    }

    public function testJavaScriptFileConfiguredAsFooterCssBreaksRendering(): void
    {
        $twig = $this->createTwig($this->createService(null, null, 'custom/footer.js'));

        try {
            $twig->render('base/__script.html.twig');
            self::fail('Rendering must fail for an invalid footer CSS configuration.');
        } catch (RuntimeError $exception) {
            self::assertStringContainsString('THEME_FOOTER_CSS must reference a .css file', $exception->getMessage());
            self::assertInstanceOf(InvalidArgumentException::class, $exception->getPrevious());
        }
    }

    public function testStartIndexLayoutRendersThemeAssetsInCorrectSections(): void
    {
        $twig = $this->createTwig(
            $this->createService(
                'custom/header.css',
                'custom/header.js',
                'custom/footer.css',
                'custom/footer.js'
            )
        );

        $html = $twig->render(
            'start/index.html.twig',
            [
                'room' => $this->createRoomStub(),
                'user' => null,
                'name' => null,
            ]
        );

        $headEnd = strpos($html, '</head>');
        $headerPos = strpos($html, '/theme/custom/header.css');
        self::assertIsInt($headEnd);
        self::assertIsInt($headerPos);
        self::assertLessThan($headEnd, $headerPos);
        self::assertStringContainsString('<link rel="stylesheet" href="/theme/custom/header.css">', $html);
        self::assertStringContainsString('<script src="/theme/custom/header.js"></script>', $html);
        self::assertStringContainsString('<link rel="stylesheet" href="/theme/custom/footer.css">', $html);
        self::assertStringContainsString('<script src="/theme/custom/footer.js"></script>', $html);

        $webpackPos = strpos($html, 'onlyConference.js');
        $footerPos = strpos($html, '/theme/custom/footer.css');
        $htmlEnd = strpos($html, '</html>');
        self::assertIsInt($webpackPos);
        self::assertIsInt($footerPos);
        self::assertIsInt($htmlEnd);
        self::assertLessThan($footerPos, $webpackPos);
        self::assertLessThan($htmlEnd, $footerPos);
    }

    public function testUppercaseExtensionIsRendered(): void
    {
        $twig = $this->createTwig($this->createService(null, 'custom/upper.JS'));

        $html = $twig->render('base/__head.html.twig', ['theme' => false]);

        self::assertStringContainsString('<script src="/theme/custom/upper.JS"></script>', $html);
    }

    private function createService(
        ?string $headerCss = null,
        ?string $headerJs = null,
        ?string $footerCss = null,
        ?string $footerJs = null
    ): ThemeAssetsService {
        return new ThemeAssetsService($this->themeDir, $headerCss, $headerJs, $footerCss, $footerJs);
    }

    private function createTwig(ThemeAssetsService $themeAssetsService): Environment
    {
        $twig = new Environment(
            new FilesystemLoader(self::TEMPLATE_DIR),
            [
                'cache' => false,
                'strict_variables' => false,
            ]
        );
        $twig->addExtension(new StringLoaderExtension());
        $twig->addGlobal('theme_assets', $themeAssetsService);
        $twig->addGlobal('app', ['request' => new Request()]);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $assetPackage = new PathPackage(
            '/',
            new EmptyVersionStrategy(),
            new RequestStackContext($requestStack)
        );

        $twig->addFunction(new TwigFunction('asset', [$assetPackage, 'getUrl']));
        $twig->addFunction(
            new TwigFunction('path', static fn (string $name): string => '/' . $name)
        );
        $twig->addFunction(
            new TwigFunction('encore_entry_link_tags', static fn (string $entry): string => '')
        );
        $twig->addFunction(
            new TwigFunction(
                'encore_entry_script_tags',
                static fn (string $entry): string => '<script src="/build/' . $entry . '.js"></script>'
            )
        );
        $twig->addFunction(
            new TwigFunction('getApplicationProperties', static fn (string $property): ?string => null)
        );
        $twig->addFunction(new TwigFunction('getThemeProperties', static fn (mixed $room = null) => false));
        $twig->addFunction(new TwigFunction('httpsAbolutUrl', static fn (string $url): string => $url));
        $twig->addFunction(new TwigFunction('absolute_url', static fn (string $url): string => $url));
        $twig->addFunction(new TwigFunction('mercure', static fn (string $topic): string => '[]'));
        $twig->addFunction(new TwigFunction('roomPermissions', static fn (): object => (object)['moderator' => false]));
        $twig->addFunction(new TwigFunction('getRecordingForRoomAndUser', static fn (): ?object => null));
        $twig->addFunction(new TwigFunction('urlFromRoom', static fn (): string => '/'));
        $twig->addFunction(new TwigFunction('getLiveKitName', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('jwtFromRoom', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('generateEncryptedSecret', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('getJwtforWebsocket', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('getUrlforWebsocket', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('vich_uploader_asset', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('addRepetiveCharacters', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('createEtherpadLink', static fn (): string => '/'));
        $twig->addFunction(new TwigFunction('createWhitebophirLink', static fn (): string => '/'));
        $twig->addFunction(new TwigFunction('getJigasiNumber', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('getJigasiPin', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('isFirefox', static fn (): bool => false));
        $twig->addFunction(new TwigFunction('isOSType', static fn (): bool => false));
        $twig->addFilter(
            new TwigFilter('color_lighten', static fn (string $color, int $amount): string => $color)
        );
        $twig->addFilter(
            new TwigFilter(
                'trans',
                static fn (string $message, array $parameters = []): string => $message
            )
        );
        $twig->addFilter(new TwigFilter('nameOfUserNoSymbol', static fn (mixed $user): string => ''));

        return $twig;
    }

    /**
     * @return object{name: string, uid: string, tag: null, totalOpenRooms: bool,
     *                moderator: bool, enableTranscription: bool, server: object}
     */
    private function createRoomStub(): object
    {
        $room = new \stdClass();
        $room->name = 'test room';
        $room->uid = 'test-uid';
        $room->tag = null;
        $room->totalOpenRooms = false;
        $room->moderator = false;
        $room->enableTranscription = false;
        $room->server = (object) [
            'url' => 'meet.example.com',
            'slugMd5' => 'abc',
            'appId' => null,
            'corsHeader' => false,
            'liveKitServer' => false,
            'enableRecording' => false,
        ];

        return $room;
    }
}
