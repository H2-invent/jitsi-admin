<?php

namespace App\Tests\Dashboard;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RoomCardRenderTest extends TestCase
{
    private Environment $twig;
    private User $user;

    protected function setUp(): void
    {
        $translator = new class () implements TranslatorInterface {
            public function trans(
                string $id,
                array $parameters = [],
                ?string $domain = null,
                ?string $locale = null
            ): string {
                return strtr($id, $parameters);
            }

            public function getLocale(): string
            {
                return 'de';
            }

            public function setLocale(string $locale): void
            {
            }
        };

        $this->twig = new Environment(
            new FilesystemLoader(__DIR__ . '/../../templates'),
            [
                'cache' => false,
                'strict_variables' => true,
            ]
        );
        $this->twig->addExtension(new TranslationExtension($translator));

        $functions = [
            'roomIsReadOnly' => fn ($room, $user) => false,
            'isAllowedToOrganize' => fn ($room, $user) => false,
            'path' => fn ($name, $params = []) => '/path/' . $name,
            'getApplicationProperties' => fn ($key) => null,
            'getThemeProperties' => fn () => null,
            'nameOfUserwithFormat' => fn ($user, $format) => 'Name',
            'getJigasiNumber' => fn ($room) => [],
            'sipPinFromRoomAndUser' => fn ($room, $user) => null,
            'createWhitebophirLink' => fn ($room, $participant) => '',
            'createEtherpadLink' => fn ($room, $user) => '',
        ];
        foreach ($functions as $name => $callback) {
            $this->twig->addFunction(new TwigFunction($name, $callback));
        }

        $this->twig->addFilter(new TwigFilter('addRepetiveCharacters', fn ($value, $char, $length) => $value));
        $this->twig->addFilter(new TwigFilter('markdown_to_html', fn ($value) => $value));

        $this->user = new User();
        $app = new class ($this->user) {
            public $request;
            public $user;

            public function __construct(User $user)
            {
                $this->request = new Request();
                $this->user = $user;
            }
        };
        $this->twig->addGlobal('app', $app);
        $this->twig->addGlobal('theme', null);
        $this->twig->addGlobal('servers', []);
        $this->twig->addGlobal('laf_showNameFrontend', 'name');
    }

    public function testBeforeStartWithoutParticipantsShowsNoBadge(): void
    {
        $this->assertNoBadge(1800, 5400, false);
    }

    public function testStartsSoonWithoutParticipantsShowsCountdown(): void
    {
        $this->assertOrangeCountdown(300, 3900, false);
    }

    public function testStartsSoonWithParticipantsStaysOrange(): void
    {
        $this->assertOrangeCountdown(300, 3900, true);
    }

    public function testBeforeStartWithParticipantsStaysOrange(): void
    {
        $this->assertOrangeCountdown(1800, 5400, true);
    }

    public function testBetweenStartAndEndWithoutParticipantsShowsGreen(): void
    {
        $this->assertGreenNow(-1800, 1800, false);
    }

    public function testBetweenStartAndEndWithParticipantsShowsGreen(): void
    {
        $this->assertGreenNow(-1800, 1800, true);
    }

    public function testAfterEndWithParticipantsStaysGreen(): void
    {
        $this->assertGreenNow(-7200, -600, true);
    }

    public function testAfterEndWithoutParticipantsShowsNoBadge(): void
    {
        $this->assertNoBadge(-7200, -600, false);
    }

    public function testWithoutEndDateWithParticipantsShowsNoBadge(): void
    {
        $this->assertNoBadge(-7200, null, true);
    }

    public function testLazyFixedRendersStartsSoonBadge(): void
    {
        $now = time();
        $room = $this->createRoom($now, 300, 3900);

        $html = $this->twig->render(
            'dashboard/__lazyFixed.html.twig',
            [
                'persistantRooms' => [$room],
                'servers' => [],
                'offset' => 1,
                'timestamp' => $now,
            ]
        );

        self::assertStringContainsString('awayBorder', $html);
        self::assertStringContainsString('Startet in 5 min', $html);
    }

    private function assertGreenNow(int $startOffset, ?int $endOffset, bool $running): void
    {
        $html = $this->renderCardAt($startOffset, $endOffset, $running);

        self::assertStringContainsString('olineBorder', $html);
        self::assertStringContainsString('bg-online', $html);
        self::assertStringContainsString('Jetzt', $html);
        self::assertStringNotContainsString('awayBorder', $html);
        self::assertStringNotContainsString('bg-away', $html);
        self::assertStringNotContainsString('Startet in', $html);
    }

    private function assertOrangeCountdown(int $startOffset, ?int $endOffset, bool $running): void
    {
        $html = $this->renderCardAt($startOffset, $endOffset, $running);

        self::assertStringContainsString('awayBorder', $html);
        self::assertStringContainsString('bg-away', $html);
        self::assertStringContainsString('Startet in ' . (int) round($startOffset / 60) . ' min', $html);
        self::assertStringNotContainsString('olineBorder', $html);
        self::assertStringNotContainsString('bg-online', $html);
        self::assertStringNotContainsString('Jetzt', $html);
    }

    private function assertNoBadge(int $startOffset, ?int $endOffset, bool $running): void
    {
        $html = $this->renderCardAt($startOffset, $endOffset, $running);

        self::assertStringNotContainsString('awayBorder', $html);
        self::assertStringNotContainsString('olineBorder', $html);
        self::assertStringNotContainsString('bg-away', $html);
        self::assertStringNotContainsString('bg-online', $html);
    }

    private function renderCardAt(int $startOffset, ?int $endOffset, bool $running): string
    {
        $now = time();
        $room = $this->createRoom($now, $startOffset, $endOffset);

        return $this->renderCard($room, $running ? [$room] : [], $now);
    }

    private function createRoom(int $now, int $startOffset, ?int $endOffset): Rooms
    {
        $server = new Server();
        $server->setServerName('Server');

        $room = new Rooms();
        $room->setName('Room card test');
        $room->setUidReal('room-card-test');
        $room->setServer($server);
        $room->setModerator($this->user);
        $room->setStart(new \DateTime('@' . ($now + $startOffset)));
        $room->setEnddate(null === $endOffset ? null : new \DateTime('@' . ($now + $endOffset)));

        return $room;
    }

    private function renderCard(Rooms $room, array $runningRooms, int $now): string
    {
        return $this->twig->render(
            'dashboard/__roomCard.html.twig',
            [
                'room' => $room,
                'runningRooms' => $runningRooms,
                'timestamp' => $now,
            ]
        );
    }
}
