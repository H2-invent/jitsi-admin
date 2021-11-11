<?php

namespace App\Service;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

class ToParticipantWebsocketService
{
    private $publisher;
    private $urlgenerator;
    private $parameterBag;
    private $logger;
    private $translator;
    private $roomService;
    private $twig;
    private $directSend;

    public function __construct(DirectSendService $directSendService, Environment $environment, HubInterface $publisher, RoomService $roomService, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->publisher = $publisher;
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->roomService = $roomService;
        $this->twig = $environment;
        $this->directSend = $directSendService;
    }

    public function acceptLobbyUser(LobbyWaitungUser $lobbyWaitungUser)
    {

        $topic = $this->urlgenerator->generate('lobby_participants_wait', array('roomUid' => $lobbyWaitungUser->getRoom()->getUid(), 'userUid' => $lobbyWaitungUser->getUser()->getUid()), UrlGeneratorInterface::ABSOLUTE_URL);
        $this->directSend->sendSnackbar($topic,$this->translator->trans('lobby.participant.accept'),'success');
        $appUrl = $this->roomService->join(
            $lobbyWaitungUser->getRoom(),
            $lobbyWaitungUser->getUser(),
            'a',
            $lobbyWaitungUser->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference'))
        );
        $browserUrl = $this->roomService->join(
            $lobbyWaitungUser->getRoom(),
            $lobbyWaitungUser->getUser(),
            'b',
            $lobbyWaitungUser->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference'))
        );

        if ($this->parameterBag->get('start_dropdown_allow_browser') == 1 && $this->parameterBag->get('start_dropdown_allow_app') == 1) {
            $content =
                $this->twig->render('lobby_participants/choose.html.twig', array('appUrl' => $appUrl, 'browserUrl' => $browserUrl));
            $this->directSend->sendModal($topic, $content);
        } elseif ($this->parameterBag->get('start_dropdown_allow_browser') == 1) {
            $this->directSend->sendRedirect($topic, $browserUrl, 5000);
        } elseif ($this->parameterBag->get('start_dropdown_allow_app') == 1) {
            $this->directSend->sendRedirect($topic, $appUrl, 5000);
        }
    }
    public function sendDecline(LobbyWaitungUser $lobbyWaitungUser){
        $topic = $this->urlgenerator->generate('lobby_participants_wait', array('roomUid' => $lobbyWaitungUser->getRoom()->getUid(), 'userUid' => $lobbyWaitungUser->getUser()->getUid()), UrlGeneratorInterface::ABSOLUTE_URL);
        $this->directSend->sendSnackbar($topic,$this->translator->trans('lobby.participant.decline'),'danger');
        $this->directSend->sendRedirect($topic,$this->urlgenerator->generate('index'),3000);
    }
}