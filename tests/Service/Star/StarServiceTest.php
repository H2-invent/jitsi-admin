<?php

namespace App\Tests\Service\Star;

use App\Repository\ServerRepository;
use App\Repository\StarRepository;
use App\Service\Star\StarService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class StarServiceTest extends KernelTestCase
{
    public function testCreateStarStoresStarForKnownServer(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $server = $container->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);
        $service = $container->get(StarService::class);

        $before = $container->get(StarRepository::class)->count([]);

        $response = $service->createStar($server->getId(), 4, 'Great tool', 'firefox', 'linux');

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(['error' => false], json_decode($response->getContent(), true));

        $stars = $container->get(StarRepository::class)->findAll();
        self::assertCount($before + 1, $stars);
        $star = $container->get(StarRepository::class)->findOneBy(['server' => $server]);
        self::assertNotNull($star);
        self::assertSame(4, $star->getStar());
        self::assertSame('Great tool', $star->getComment());
        self::assertSame('firefox', $star->getBrowser());
        self::assertSame('linux', $star->getOs());
        self::assertNotNull($star->getCreatedAt());
    }

    public function testCreateStarWithoutCommentAndOptionalFields(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $server = $container->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si3']);
        $service = $container->get(StarService::class);

        $response = $service->createStar($server->getId(), 2, '', null, null);

        self::assertSame(['error' => false], json_decode($response->getContent(), true));

        $star = $container->get(StarRepository::class)->findOneBy(['server' => $server]);
        self::assertNotNull($star);
        self::assertSame(2, $star->getStar());
        self::assertNull($star->getComment());
        self::assertNull($star->getBrowser());
        self::assertNull($star->getOs());
    }

    public function testCreateStarWithUnknownServerDoesNotPersist(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $service = $container->get(StarService::class);
        $before = $container->get(StarRepository::class)->count([]);

        $response = $service->createStar(999999, 5, 'unknown server', 'chrome', 'mac');

        self::assertSame(['error' => false], json_decode($response->getContent(), true));
        self::assertSame($before, $container->get(StarRepository::class)->count([]));
    }
}
