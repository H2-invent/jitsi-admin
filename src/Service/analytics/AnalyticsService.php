<?php

namespace App\Service\analytics;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
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

        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(r.id)')->from(Rooms::class, 'r');
        $res['rooms'] = (int)$qb->getQuery()->getSingleScalarResult();

        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(u.id)')->from(User::class, 'u');
        $res['users'] = (int)$qb->getQuery()->getSingleScalarResult();

        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(u.id)')->from(User::class, 'u')->where($qb->expr()->isNotNull('u.keycloakId'));
        $res['kcUser'] = (int)$qb->getQuery()->getSingleScalarResult();

        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(r.id)')->from(Rooms::class, 'r')->where('r.totalOpenRooms = true');
        $res['openRooms'] = (int)$qb->getQuery()->getSingleScalarResult();

        $res['jitsiadmin_version'] = $this->parameterBag->get('laF_version');

        $qb = $em->createQueryBuilder();
        $qb->select('DISTINCT r.hostUrl')->from(Rooms::class, 'r')->where($qb->expr()->isNotNull('r.hostUrl'));
        $res['urls'] = array_values(array_filter($qb->getQuery()->getSingleColumnResult()));

        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(u.id)')->from(Rooms::class, 'r')->innerJoin('r.user', 'u');
        $totalParticipants = (int)$qb->getQuery()->getSingleScalarResult();

        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(DISTINCT r.id)')->from(Rooms::class, 'r')->innerJoin('r.user', 'u');
        $roomsWithParticipants = (int)$qb->getQuery()->getSingleScalarResult();

        $res['average_room_size'] = $roomsWithParticipants > 0 ? $totalParticipants / $roomsWithParticipants : 0;

        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(s.id)')->from(Server::class, 's');
        $res['servers_amount'] = (int)$qb->getQuery()->getSingleScalarResult();

        $qb = $em->createQueryBuilder();
        $qb->select('s.url')->from(Server::class, 's');
        $res['server_url'] = array_values(array_filter($qb->getQuery()->getSingleColumnResult()));

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
