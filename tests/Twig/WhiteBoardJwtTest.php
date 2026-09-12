<?php

namespace App\Tests\Twig;

use App\Repository\RoomsRepository;
use App\Twig\WhiteBoardJwt;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class WhiteBoardJwtTest extends KernelTestCase
{
    private function extension(): WhiteBoardJwt
    {
        return self::getContainer()->get(WhiteBoardJwt::class);
    }

    private function functionByName(WhiteBoardJwt $extension, string $name): TwigFunction
    {
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }
        self::fail(sprintf('Twig function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('getJwtforWhiteboard', $functions[0]->getName());
        $this->assertSame([$extension, 'getJwtforWhiteboard'], $functions[0]->getCallable());
    }

    public function testGetJwtforWhiteboardCreatesEditorTokenByDefault(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $secret = self::getContainer()->getParameter('WHITEBOARD_SECRET');

        $payload = (array) JWT::decode($extension->getJwtforWhiteboard($room), new Key($secret, 'HS256'));

        $this->assertSame(['editor:' . $room->getUidReal()], $payload['roles']);
        $this->assertGreaterThan($payload['iat'], $payload['exp']);
        $this->assertLessThanOrEqual(3 * 24 * 3600, $payload['exp'] - $payload['iat']);
        $this->assertGreaterThanOrEqual(3 * 24 * 3600 - 1, $payload['exp'] - $payload['iat']);
    }

    public function testGetJwtforWhiteboardCreatesModeratorTokenWhenRequested(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $secret = self::getContainer()->getParameter('WHITEBOARD_SECRET');

        $payload = (array) JWT::decode($extension->getJwtforWhiteboard($room, true), new Key($secret, 'HS256'));

        $this->assertSame(['moderator:' . $room->getUidReal()], $payload['roles']);
    }

    public function testGetJwtforWhiteboardViaRegisteredCallable(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $secret = self::getContainer()->getParameter('WHITEBOARD_SECRET');

        $token = call_user_func($this->functionByName($extension, 'getJwtforWhiteboard')->getCallable(), $room);
        $payload = (array) JWT::decode($token, new Key($secret, 'HS256'));

        $this->assertSame(['editor:' . $room->getUidReal()], $payload['roles']);
    }
}
