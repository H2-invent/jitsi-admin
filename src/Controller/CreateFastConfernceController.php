<?php

namespace App\Controller;

use App\Entity\Server;
use App\Repository\ServerRepository;
use App\Service\CreateHttpsUrl;
use App\Service\PublicConference\PublicConferenceService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateFastConfernceController extends AbstractController
{
    private Server $server;

    public function __construct(

        private readonly ServerRepository        $serverRepository,
        private readonly ThemeService            $themeService,
        private readonly PublicConferenceService $publicConferenceService,
        private readonly CreateHttpsUrl          $createHttpsUrl,
        private readonly TranslatorInterface     $translator,
        private readonly EntityManagerInterface  $entityManager,
    )
    {
        $this->server = $this->serverRepository->find($this->themeService->getApplicationProperties('PUBLIC_SERVER'));

    }

    #[Route('/room/create/fast/confernce', name: 'app_create_fast_confernce')]
    public function index(Request $request): Response
    {
        try {
            if ($this->server) {
                $room = $this->publicConferenceService->createNewRoomFromName(md5(uniqid()), $this->server);
                $room->setModerator($this->getUser());
                $room->setPublic(true);
                $room->setTotalOpenRooms(true);
                $this->entityManager->persist($room);
                $this->entityManager->flush();
                return new JsonResponse(
                    [
                        'redirectUrl' => $this->generateUrl('dashboard'),
                        'popups' => [
                            ['url' => $this->createHttpsUrl->createHttpsUrl($this->generateUrl('room_join', ['t' => 'b', 'room' => $room->getId()])), 'title' => $room->getName()]
                        ]
                    ]
                );
            } else {

                $this->addFlash('danger', $this->translator->trans('Fehler'));
                return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
            }
        } catch (\Exception $exception) {
            $this->addFlash('danger', $this->translator->trans('Fehler'));
            return new JsonResponse(['redirectUrl' => $this->generateUrl('dashboard')]);
        }
    }
}
