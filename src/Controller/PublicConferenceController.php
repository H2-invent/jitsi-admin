<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Form\Type\PublicConferenceType;
use App\Helper\JitsiAdminController;
use App\Service\PublicConference\PublicConferenceService;
use App\Service\ThemeService;
use App\Service\webhook\RoomStatusFrontendService;
use App\UtilsHelper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PublicConferenceController extends JitsiAdminController
{
    private ?Server $server;

    public function __construct(
        ManagerRegistry                   $managerRegistry,
        TranslatorInterface               $translator,
        LoggerInterface                   $logger,
        ParameterBagInterface             $parameterBag,
        private ThemeService              $themeService,
        private RequestStack              $requestStack,
        private RoomStatusFrontendService $roomStatusFrontendService,
        private PublicConferenceService   $publicConferenceService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->server = $this->doctrine->getRepository(Server::class)->find($this->themeService->getApplicationProperties('PUBLIC_SERVER'));
    }

    #[Route('/m', name: 'app_public_form')]
    public function index(Request $request): Response
    {
        if (!$this->server) {
            return $this->redirectToRoute('dashboard');
        }

        $data = array('roomName' => UtilsHelper::readable_random_string(20));
        $form = $this->createForm(PublicConferenceType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $room = $this->publicConferenceService->createNewRoomFromName($data['roomName'], $this->server);
            return $this->redirectToRoute('app_public_conference', array('confId' => $room->getName()));

        }
        return $this->render('public_conference/index.html.twig', [
            'form' => $form->createView(),
            'server' => $this->server
        ]);
    }

    #[Route('/m/{confId}', name: 'app_public_conference')]
    public function startMeeting($confId): Response
    {

        $room = $this->publicConferenceService->createNewRoomFromName($confId, $this->server);
        $firstUser = $this->roomStatusFrontendService->isRoomCreated($room);
        return $this->render('start/index.html.twig', [
            'room' => $room,
            'user' => null,
            'name' => 'Jitsi-Fellower',
            'moderator' => !$firstUser
        ]);
    }

    /**
     * @return Server|mixed|object|null
     */
    public function getServer(): mixed
    {
        return $this->server;
    }

    /**
     * @param Server|mixed|object|null $server
     */
    public function setServer(mixed $server): void
    {
        $this->server = $server;
    }


}
