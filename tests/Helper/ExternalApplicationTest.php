<?php

namespace App\Tests\Helper;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Helper\ExternalApplication;
use App\Service\Theme\ThemeService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExternalApplicationTest extends KernelTestCase
{
    private ExternalApplication $externalApplication;
    private ThemeService $themeService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->externalApplication = $container->get(ExternalApplication::class);
        $this->themeService = $container->get(ThemeService::class);
    }

    private function room(string $uidReal = 'test123'): Rooms
    {
        return (new Rooms())
            ->setUidReal($uidReal)
            ->setUid('test5432');
    }

    private function applicationUrl(string $property): string
    {
        return $this->themeService->getApplicationProperties($property);
    }

    private function jwtPayload(string $token): array
    {
        $parts = explode('.', $token);
        self::assertCount(3, $parts);

        return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
    }

    public function testEtherpadLinkUsesPlaceholderNameByDefault(): void
    {
        $room = $this->room();

        self::assertSame(
            $this->applicationUrl('ETHERPAD_URL') . '/p/test123?showChat=false&userName=%name%',
            $this->externalApplication->etherpadLink($room)
        );
    }

    public function testEtherpadLinkUrlEncodesProvidedName(): void
    {
        $room = $this->room();

        self::assertSame(
            $this->applicationUrl('ETHERPAD_URL') . '/p/test123?showChat=false&userName=John+Doe',
            $this->externalApplication->etherpadLink($room, 'John Doe')
        );
    }

    public function testEtherpadLinkUsesRepeaterUid(): void
    {
        $room = $this->room()->setRepeater((new Repeat())->setUid('repeater-uid'));

        self::assertSame(
            $this->applicationUrl('ETHERPAD_URL') . '/p/repeater-uid?showChat=false&userName=%name%',
            $this->externalApplication->etherpadLink($room)
        );
    }

    public function testWhitebophirLinkContainsEditorJwt(): void
    {
        $room = $this->room();
        $prefix = $this->applicationUrl('WHITEBOARD_URL') . '/boards/test123?token=';
        $link = $this->externalApplication->whitebophirLink($room);

        self::assertStringStartsWith($prefix, $link);

        $payload = $this->jwtPayload(substr($link, strlen($prefix)));
        self::assertSame(['editor:test123'], $payload['roles']);
        self::assertArrayHasKey('iat', $payload);
        self::assertArrayHasKey('exp', $payload);
    }

    public function testWhitebophirLinkContainsModeratorJwt(): void
    {
        $room = $this->room();
        $prefix = $this->applicationUrl('WHITEBOARD_URL') . '/boards/test123?token=';
        $link = $this->externalApplication->whitebophirLink($room, true);

        self::assertStringStartsWith($prefix, $link);

        $payload = $this->jwtPayload(substr($link, strlen($prefix)));
        self::assertSame(['moderator:test123'], $payload['roles']);
    }
}
