<?php

namespace App\Tests\Dashboard;

use App\Repository\UserRepository;
use App\Service\Dashboard\DashboardViewService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DashboardPastDeleteUrlTest extends KernelTestCase
{
    public function testPastRoomRemoveUrlsContainUserParameter(): void
    {
        self::bootKernel();

        $userRepo = self::getContainer()->get(UserRepository::class);
        $dashboardViewService = self::getContainer()->get(DashboardViewService::class);

        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertNotNull($user);

        $page = $dashboardViewService->buildPastPage($user, 0);

        $removeUrls = [];
        foreach ($page['rooms'] as $room) {
            $urls = [];
            if (is_array($room['actions']['leave'] ?? null)) {
                $urls[] = $room['actions']['leave']['href'];
            }
            foreach ($room['actions']['optionItems'] ?? [] as $item) {
                if (isset($item['href'])) {
                    $urls[] = $item['href'];
                }
            }
            foreach ($urls as $url) {
                if (str_contains((string) $url, '/room/participant/remove')) {
                    $removeUrls[] = ['room' => $room['id'], 'url' => $url];
                }
            }
        }

        self::assertGreaterThan(0, count($removeUrls), 'Expected at least one past room remove url on the dashboard');
        foreach ($removeUrls as $entry) {
            self::assertStringContainsString(
                'user=' . $user->getId(),
                $entry['url'],
                'Past room remove url for room ' . $entry['room'] . ' must contain the current user id'
            );
        }
    }
}
