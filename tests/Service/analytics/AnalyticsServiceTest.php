<?php

namespace App\Tests\Service\analytics;

use App\Entity\Server;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\analytics\AnalyticsService;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AnalyticsServiceTest extends KernelTestCase
{
    public function testGatherInformationsReturnsShapeDerivedFromFixtures(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $service = $container->get(AnalyticsService::class);

        $roomsRepository = $container->get(RoomsRepository::class);
        $userRepository = $container->get(UserRepository::class);
        $serverRepository = $container->get(ServerRepository::class);

        $result = $service->gatherInformations();

        $rooms = $roomsRepository->findAll();
        self::assertGreaterThan(0, count($rooms));

        $expectedUrls = [];
        $averageSum = 0;
        $averageCounter = 0;
        foreach ($rooms as $room) {
            if (!in_array($room->getHostUrl(), $expectedUrls)) {
                $expectedUrls[] = $room->getHostUrl();
            }
            if (count($room->getUser()) > 0) {
                $averageSum += count($room->getUser());
                $averageCounter++;
            }
        }
        $expectedAverage = $averageCounter !== 0 ? ($averageSum / $averageCounter) : 0;

        $servers = $serverRepository->findAll();
        $expectedServerUrls = array_map(static fn(Server $server): ?string => $server->getUrl(), $servers);

        self::assertSame('jitsi-admin', $result['data']);
        self::assertSame(count($rooms), $result['rooms']);
        self::assertSame(count($userRepository->findAll()), $result['users']);
        self::assertGreaterThan(0, $result['users']);
        self::assertSame(count($userRepository->findUsersWithKC()), $result['kcUser']);
        self::assertSame($container->getParameter('laF_version'), $result['jitsiadmin_version']);
        self::assertSame(count($roomsRepository->findBy(['totalOpenRooms' => true])), $result['openRooms']);
        self::assertGreaterThan(0, $result['openRooms']);
        self::assertSame($expectedAverage, $result['average_room_size']);
        self::assertSame($expectedUrls, $result['urls']);
        self::assertContains('http://localhost:8000', $result['urls']);
        self::assertSame(count($servers), $result['servers_amount']);
        self::assertSame($expectedServerUrls, $result['server_url']);
        self::assertContains('meet.jit.si', $result['server_url']);
        self::assertArrayNotHasKey('theme', $result);
    }

    public function testSendAnalyticsPostsGatheredDataToStatsEndpoint(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // The production code creates its own FilesystemAdapter, so clear the key it uses.
        $cache = new FilesystemAdapter();
        $cache->delete('send_analytics');

        $response = $this->createMock(ResponseInterface::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $captured = null;
        $httpClient->expects(self::once())
            ->method('request')
            ->willReturnCallback(
                function (
                    string $method,
                    string $url,
                    array $options = []
                ) use (
                    &$captured,
                    $response
                ): ResponseInterface {
                    $captured = [
                        'method' => $method,
                        'url' => $url,
                        'options' => $options,
                    ];

                    return $response;
                }
            );

        // Use a dedicated ParameterBag so the send branch is exercised regardless of the
        // DONT_SEND_TELEMATIC value configured in the environment.
        $parameterBag = new ParameterBag(
            [
                'DONT_SEND_TELEMATIC' => 'notcorrect',
                'laF_version' => $container->getParameter('laF_version'),
            ]
        );

        $service = new AnalyticsService(
            $container->get(EntityManagerInterface::class),
            $httpClient,
            $parameterBag,
            $container->get(ThemeService::class)
        );

        $service->sendAnalytics();

        self::assertNotNull($captured, 'The analytics POST request was not issued.');
        self::assertSame('POST', $captured['method']);
        self::assertSame('https://stats.jitsi-admin.de/analytics', $captured['url']);
        self::assertSame(10, $captured['options']['timeout']);
        self::assertArrayHasKey('body', $captured['options']);
        self::assertArrayHasKey('data', $captured['options']['body']);

        $payload = json_decode($captured['options']['body']['data'], true);
        self::assertSame('jitsi-admin', $payload['data']);
        self::assertSame($container->getParameter('laF_version'), $payload['jitsiadmin_version']);
        self::assertSame(count($container->get(RoomsRepository::class)->findAll()), $payload['rooms']);
    }
}
