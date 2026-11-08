<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Provisioner\ProvisionerRecordService;
use App\Service\Provisioner\ProvisionerService;
use App\Service\StartMeetingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProvisionerController extends AbstractController
{
    public function __construct(
        private readonly ProvisionerService $provisionerService,
        private readonly ProvisionerRecordService $provisionerRecordService,
        private readonly StartMeetingService $startMeetingService,
    )
    {
    }

    #[Route('/provision/{uidReal}', name: 'app_provisioner_create')]
    public function create(Rooms $room): Response
    {
        if ($room->getServer()->isProvisioningEnabled() !== true) {
            throw $this->createNotFoundException('Provisioning not enabled for this server.');
        }

        $runningProvisioning = $this->provisionerRecordService->getRunningProvisioning($room);
        if ($runningProvisioning) {
            return $this->redirectToRoute('room_join', ['type' => 'b', 'room' => $room->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$this->startMeetingService->isAllowedToEnter($room, $user)) {
            throw $this->createAccessDeniedException('User not allowed to enter room, so we will not let him provision.');
        }

        $this->provisionerService->startProvision($room);

        return $this->redirectToRoute('app_provisioner_wait', ['uidReal' => $room->getUidReal()]);
    }


    #[Route('/provision/{uidReal}/wait', name: 'app_provisioner_wait')]
    public function wait(Rooms $room): Response
    {
        if ($room->getServer()->isProvisioningEnabled() !== true) {
            throw $this->createNotFoundException('Provisioning not enabled for this server.');
        }

        $runningProvisioning = $this->provisionerRecordService->getRunningProvisioning($room);
        if ($runningProvisioning !== null) {
            return $this->redirectToRoute('room_join', ['type' => 'b', 'room' => $room->getId()]);
        }

        return $this->render('provisioner/wait.html.twig', ['room' => $room]);
    }
}
