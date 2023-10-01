<?php

namespace App\Service\analytics;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\ThemeService;
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
        $average = 0;
        $averageCounter = 0;
        $res = ['data' => 'jitsi-admin'];
        $rooms = $this->entityManager->getRepository(Rooms::class)->findAll();
        $res['rooms'] = count($rooms);
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $res['users'] = count($users);
        $usersKC = $this->entityManager->getRepository(User::class)->findUsersWithKC();
        $res['kcUser'] = count($usersKC);
        $res['jitsiadmin_version'] = $this->parameterBag->get('laF_version');
        $openRooms = $this->entityManager->getRepository(Rooms::class)->findBy(['totalOpenRooms' => true]);
        $res['openRooms'] = count($openRooms);
        $url = [];
        foreach ($rooms as $data) {
            if (!in_array($data->getHostUrl(), $url)) {
                $url[] = $data->getHostUrl();
            }
            if (count($data->getUser()) > 0) {
                $average += count($data->getUser());
                $averageCounter++;
            }

        }
        $average = $averageCounter!=0?($average / $averageCounter):0;
        $res['average_room_size'] = $average;
        $res['urls'] = $url;
        $server = $this->entityManager->getRepository(Server::class)->findAll();
        $res['servers_amount'] = count($server);
        $serverArr = [];
        foreach ($server as $data2) {
            $serverArr[] = $data2->getUrl();
        }
        $res['server_url'] = $serverArr;
        $theme = $this->themeService->showAllThemes();
        if ($theme){
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
