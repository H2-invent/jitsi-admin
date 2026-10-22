<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Service\RoomService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ToParticipantWebsocketService
{
    private UrlGeneratorInterface $urlgenerator;
    private ParameterBagInterface $parameterBag;
    private TranslatorInterface $translator;
    private RoomService $roomService;
    private DirectSendService $directSend;

    public function __construct(DirectSendService $directSendService, RoomService $roomService, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, TranslatorInterface $translator)
    {
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->roomService = $roomService;
        $this->directSend = $directSendService;
    }

    public function setDirectSend(DirectSendService $directSendService): void
    {
        $this->directSend = $directSendService;
    }

    public function acceptLobbyUser(LobbyWaitungUser $lobbyWaitungUser): void
    {
        $options = [];
        $topic = 'lobby_WaitingUser_websocket/' . $lobbyWaitungUser->getUid();
        $this->directSend->sendSnackbar($topic, $this->translator->trans('lobby.participant.accept'), 'success',2000);
        $appUrl = $this->roomService->join(
            $lobbyWaitungUser->getRoom(),
            $lobbyWaitungUser->getUser(),
            'a',
            $lobbyWaitungUser->getShowName()
        );

        if ($lobbyWaitungUser->getType() === 'b') {


            if ($lobbyWaitungUser->getRoom()->getServer()->getAppId()) {
                $options['jwt'] = $this->roomService->generateJwt($lobbyWaitungUser->getRoom(), $lobbyWaitungUser->getUser(), $lobbyWaitungUser->getShowName());
            }

            if ($lobbyWaitungUser->getRoom()->getServer()->getCorsHeader()) {
                $browserUrl = $this->roomService->join(
                    $lobbyWaitungUser->getRoom(),
                    $lobbyWaitungUser->getUser(),
                    'b',
                    $lobbyWaitungUser->getShowName()
                );
                $this->directSend->sendRedirect($topic, $browserUrl, 5000);
                $this->directSend->sendRedirect($topic, '/', 6000);
            } else {
                $this->directSend->sendNewJitsiMeeting($topic, $options);
            }
        } elseif ($lobbyWaitungUser->getType() === 'a') {
            $this->directSend->sendRedirect($topic, $appUrl, 5000);
            $this->directSend->sendRedirect($topic, '/', 6000);
        }

    }

    public function sendDecline(LobbyWaitungUser $lobbyWaitungUser): void
    {
        $topic = 'lobby_WaitingUser_websocket/' . $lobbyWaitungUser->getUid();
        $this->directSend->sendSnackbar($topic, $this->translator->trans('lobby.participant.decline'), 'danger',2000);
        $this->directSend->sendRedirect($topic, $this->urlgenerator->generate('index'), $this->parameterBag->get('laf_lobby_popUpDuration'));
    }
    /**
     * @param string|null $message
     * @param string $from
     * @return void
     */
    public function sendMessage(LobbyWaitungUser $lobbyWaitungUser, $message, string $from): void
    {
        $topic = 'lobby_WaitingUser_websocket/' . $lobbyWaitungUser->getUid();
        $this->directSend->sendSnackbar($topic,$message,'red',10000);

    }
}
