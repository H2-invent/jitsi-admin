<?php

namespace App\Tests\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\ServerRepository;
use App\Service\JoinUrlGeneratorService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JoinUrlGeneratorServiceTest extends KernelTestCase
{
    private function service(): JoinUrlGeneratorService
    {
        return self::getContainer()->get(JoinUrlGeneratorService::class);
    }

    private function server(): \App\Entity\Server
    {
        return self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
    }

    private function room(bool $persistantRoom, string $uid): Rooms
    {
        $room = new Rooms();
        $room->setUid($uid);
        $room->setPersistantRoom($persistantRoom);
        $room->setServer($this->server());

        return $room;
    }

    private function user(string $email): User
    {
        $user = new User();
        $user->setEmail($email);

        return $user;
    }

    public function testGenerateUrlForPersistantRoomUsesUidRouteAndEncodedData(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $server = $this->server();
        $uid = 'persistant-room-uid';
        $email = 'persistant@local.de';

        $url = $this->service()->generateUrl($this->room(true, $uid), $this->user($email));

        $encoded = base64_encode('uid=' . $uid . '&email=' . $email);
        self::assertSame($encoded, $this->queryParameter($url, 'data'));
        self::assertSame('uid=' . $uid . '&email=' . $email, base64_decode($this->queryParameter($url, 'data')));
        self::assertSame('/join/' . $server->getSlug() . '/' . $uid, parse_url($url, PHP_URL_PATH));
        self::assertStringStartsWith($container->getParameter('laF_baseUrl'), $url);
    }

    public function testGenerateUrlForNonPersistantRoomOmitsUidRoute(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $server = $this->server();
        $uid = 'non-persistant-room-uid';
        $email = 'non-persistant@local.de';

        $url = $this->service()->generateUrl($this->room(false, $uid), $this->user($email));

        $encoded = base64_encode('uid=' . $uid . '&email=' . $email);
        self::assertSame($encoded, $this->queryParameter($url, 'data'));
        self::assertSame('/join/' . $server->getSlug(), parse_url($url, PHP_URL_PATH));
        self::assertStringNotContainsString($uid, (string) parse_url($url, PHP_URL_PATH));
        self::assertStringStartsWith($container->getParameter('laF_baseUrl'), $url);
    }

    private function queryParameter(string $url, string $name): ?string
    {
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query[$name] ?? null;
    }
}
