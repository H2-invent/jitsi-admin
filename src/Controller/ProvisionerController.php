<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Service\ProvisionerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProvisionerController extends AbstractController
{
    public function __construct(
        private ProvisionerService $provisionerService,
    )
    {
    }

    #[Route('/provision/{uidReal}', name: 'app_provisioner_create')]
    public function create(Rooms $room): Response
    {
        $this->provisionerService->provisionNewServerForRoom($room);

        return $this->redirectToRoute('app_provisioner_wait', ['uidReal' => $room->getUidReal()]);
    }


    #[Route('/provision/{uidReal}/wait', name: 'app_provisioner_wait')]
    public function wait(Rooms $room): Response
    {
        return $this->render('provisioner/wait.html.twig', [
            'room' => $room,
        ]);
    }
}
