<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\User;
use App\Helper\UidGeneratorHelper;
use App\Repository\ServerRepository;
use App\Service\CreateHttpsUrl;
use App\Service\PublicConference\PublicConferenceService;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateFastConferenceController extends AbstractController
{
    private readonly ?Server $server;

    public function __construct(

        private readonly ServerRepository        $serverRepository,
        private readonly ThemeService            $themeService,
        private readonly PublicConferenceService $publicConferenceService,
        private readonly CreateHttpsUrl          $createHttpsUrl,
        private readonly TranslatorInterface     $translator,
        private readonly EntityManagerInterface  $entityManager,
    ) {
        $this->server = $this->serverRepository->find($this->themeService->getApplicationProperties('PUBLIC_SERVER'));
    }

    #[Route('/room/create/fast/conference', name: 'app_create_fast_conference')]
    public function index(): Response
    {
        try {
            if ($this->server) {
                $room = $this->publicConferenceService->createNewRoomFromName(UidGeneratorHelper::generate(), $this->server);
                /** @var User $user */
                $user = $this->getUser();
                $room
                    ->setModerator($user)
                    ->setPublic(true)
                    ->setTotalOpenRooms(true)
                    ->setIsFastConference(true);
                $this->entityManager->persist($room);
                $this->entityManager->flush();

                if ($this->server->shouldProvisionNewServer()) {
                    $url = $this->generateUrl('app_provisioner_create', ['uidReal' => $room->getUidReal()]);
                } else {
                    $url = $this->createHttpsUrl->createHttpsUrl($this->generateUrl('room_join', ['type' => 'b', 'room' => $room->getId()]));
                }

                return new JsonResponse(
                    [
                        'redirectUrl' => $this->generateUrl('dashboard'),
                        'popups' => [
                            ['url' => $url, 'title' => $room->getName()],
                        ],
                    ]
                );
            }

            $this->addFlash('danger', $this->translator->trans('Fehler'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        } catch (\Exception) {
            $this->addFlash('danger', $this->translator->trans('Fehler'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }
    }
}
