<?php

namespace App\Service\analytics;

use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AnalyticsService
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface    $httpClient,
        private ParameterBagInterface  $parameterBag,
        private ThemeService           $themeService,
    )
    {
    }

    public function gatherInformations(): array
    {
        $em = $this->entityManager;
        $res = ['data' => 'jitsi-admin'];
        $res['rooms'] = (int)$em->createQuery('SELECT COUNT(r) FROM App\Entity\Rooms r')->getSingleScalarResult();
        $res['users'] = (int)$em->createQuery('SELECT COUNT(u) FROM App\Entity\User u')->getSingleScalarResult();
        $res['kcUser'] = (int)$em->createQuery('SELECT COUNT(u) FROM App\Entity\User u WHERE u.keycloakId IS NOT NULL')->getSingleScalarResult();
        $res['openRooms'] = (int)$em->createQuery('SELECT COUNT(r) FROM App\Entity\Rooms r WHERE r.totalOpenRooms = true')->getSingleScalarResult();
        $res['jitsiadmin_version'] = $this->parameterBag->get('laF_version');

        $urls = $em->createQuery('SELECT DISTINCT r.hostUrl FROM App\Entity\Rooms r WHERE r.hostUrl IS NOT NULL')->getSingleColumnResult();
        $res['urls'] = array_values(array_filter($urls));
        $average = $em->getConnection()->fetchOne(
            'SELECT AVG(cnt) FROM (SELECT COUNT(*) AS cnt FROM rooms_user GROUP BY rooms_id HAVING COUNT(*) > 0) t'
        );

        $res['average_room_size'] = $average !== false ? (float)$average : 0;
        $res['servers_amount'] = (int)$em->createQuery('SELECT COUNT(s) FROM App\Entity\Server s')->getSingleScalarResult();
        $serverUrl = $em->createQuery('SELECT s.url FROM App\Entity\Server s')->getSingleColumnResult();
        $res['server_url'] = array_values(array_filter($serverUrl));

        $theme = $this->themeService->showAllThemes();
        if ($theme) {
            $res['theme'] = $theme;
        }

        return $res;
    }

    public function sendAnalytics(): void
    {
        if (md5($this->parameterBag->get('DONT_SEND_TELEMATIC')) !== '1d824017272c3c2fbe01f151ae7819b6') {
            $cache = new FilesystemAdapter();
            $cache->get('send_analytics', function (ItemInterface $item) {
                $item->expiresAfter(12 * 60 * 60);
                try {
                    $data = $this->gatherInformations();
                    $res = false;
                    $this->httpClient->request(
                        'POST',
                        'https://stats.jitsi-admin.de/analytics',
                        [
                            'body' => [
                                'data' => json_encode($data)
                            ],
                            'timeout' => 10
                        ]
                    );
                    $res = true;
                } catch (\Exception $exception) {
                    $res = false;
                }
                return $res;
            });
        }

    }
}
