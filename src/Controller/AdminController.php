<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Star;
use App\Helper\JitsiAdminController;
use App\Service\AdminService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminController extends JitsiAdminController
{
    #[Route(path: '/admin/server/{server}', name: 'admin_server')]
    public function server(
        ParameterBagInterface $parameterBag,
        #[MapEntity(id: 'server')] Server $server,
        AdminService $adminService,
        HttpClientInterface $httpClient,
        TranslatorInterface $translator)
    {
        $countPart = 0;
        foreach ($server->getRooms() as $room) {
            $countPart = $countPart + count($room->getUser());
        }

        if (!in_array($this->getUser(), $server->getUser()->toArray())) {
            $this->addFlash('danger', $translator->trans('Fehler, Der Server wurde nicht gefunden'));
             return $this->redirectToRoute('dashboard');
        }


        $chart = $adminService->createChart($server);
        $lastStars = $this->doctrine->getRepository(Star::class)->findBy(['server' => $server], ['createdAt' => 'DESC'], 5);
        $average = 0;
        foreach ($lastStars as $data) {
            $average += $data->getStar();
        }
        if (sizeof($lastStars) > 0) {
            $average = $average / sizeof($lastStars);
        }
        return $this->render(
            'admin/modalChart.html.twig',
            [
                'server' => $server,
                'countPart' => $countPart,
                'chart' => $chart,
                'lastAverage' => $average
            ]
        );
    }
}
