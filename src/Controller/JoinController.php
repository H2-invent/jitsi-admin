<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\JoinViewType;
use App\Helper\JitsiAdminController;
use App\Service\JoinService;
use App\Service\RoomService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class JoinController extends JitsiAdminController
{
    private $joinService;

    public function __construct(
        ManagerRegistry       $managerRegistry,
        TranslatorInterface   $translator,
        LoggerInterface       $logger,
        ParameterBagInterface $parameterBag,
        JoinService           $joinService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
        $this->joinService = $joinService;
    }

    #[Route(path: '/join/{slug}', name: 'join_index')]
    #[Route(path: '/join/{slug}/{uid}', name: 'join_index_uid')]
    #[Route(path: '/join', name: 'join_index_no_slug')]
    public function index(Request $request, TranslatorInterface $translator, RoomService $roomService, $slug = null, $uid = null)
    {
        $data = [];
        $server = $this->doctrine->getRepository(Server::class)->findOneBy(['slug' => $slug]);
        // dataStr wird mit den Daten uid und email encoded übertragen. Diese werden daraufhin als Vorgaben in das Formular eingebaut
        $dataStr = $request->get('data', '');
        $snack = $request->get('snack');
        $color = 'success';
        $dataAll = base64_decode($dataStr);
        $data = [];
        $room = null;

        parse_str($dataAll, $data);
        if ($request->cookies->get('name')) {
            $data['name'] = $request->cookies->get('name');
        }

        if (isset($data['email']) && isset($data['uid'])) {
            $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uid' => $data['uid']]);
            $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => $data['email']]);

            //If the room ID is correct set and the room exists
            if ($this->onlyWithUserAccount($room)) {
                return $this->redirectToRoute('room_join', ['room' => $room->getId(), 't' => 'b']);
            }
        } else {
            $snack = $translator->trans('Zugangsdaten in das Formular eingeben');
        }

        if ($this->parameterBag->get('laF_onlyRegisteredParticipents') == 1) {
            return $this->redirectToRoute('dashboard');
        }

        $form = $this->createForm(JoinViewType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //here is where the magic happens
            $res = $this->joinService->join($form->getData(), $snack, $color, $form->has('joinApp'), $form->has('joinApp') ? $form->get('joinApp')->isClicked() : null, $form->has('joinBrowser'), $form->has('joinBrowser') ? $form->get('joinBrowser')->isClicked() : null);
            if ($res) {
                return $res;
            }
        }
        $this->addFlash($color, $snack);
        return $this->render(
            'join/index.html.twig',
            [
                'form' => $form->createView(),
                'server' => $server,
            ]
        );
    }

    /**
     * function onlyWithUserAccount
     * Return if only users with account can join the conference
     * @return boolean
     * @author Andreas Holzmann
     */
    function onlyWithUserAccount(?Rooms $room)
    {
        if ($room) {
            return $this->parameterBag->get('laF_onlyRegisteredParticipents') == 1 || //only registered Users globally set
                $room->getOnlyRegisteredUsers();
        }
        return false;
    }

    /**
     * function userAccountLogin
     * Return boolean if account must login to join the conference
     * @return boolean
     * @author Andreas Holzmann
     */
    function userAccountLogin(?Rooms $room, ?User $user)
    {
        if ($room) {
            return $user && $user->getKeycloakId() !== null; // Registered Users have to login before they can join the conference
        }
        return false;
    }
}
