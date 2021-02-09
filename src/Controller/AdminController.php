<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\AdminService;
use Doctrine\DBAL\Types\DateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminController extends AbstractController
{

    /**
     * @Route("/admin/server/{server}", name="admin_server")
     * @ParamConverter("server", class="App\Entity\Server",options={"mapping": {"server": "id"}})
     */
    public function server(Server $server, AdminService $adminService, HttpClientInterface $httpClient, TranslatorInterface $translator)
    {
        $countPart = 0;
        foreach ($server->getRooms() as $room) {
            $countPart = $countPart + count($room->getUser());
        }

        if ($this->getUser() !== $server->getAdministrator()) {
             return $this->redirectToRoute('dashboard',['snack'=>$translator->trans('Fehler, Der Server wurde nicht gefunden'),'color'=>'danger']);
        }

        $req = $httpClient->request('GET', 'https://api.github.com/repos/H2-invent/jitsi-admin/tags');
        $tags = json_decode($req->getContent(), true);
        $chart = $adminService->createChart($server);

        return $this->render('admin/modalChart.html.twig', [
            'server' => $server,
            'countPart' => $countPart,
            'chart' => $chart,
            'tags' => $tags
        ]);

    }
}
