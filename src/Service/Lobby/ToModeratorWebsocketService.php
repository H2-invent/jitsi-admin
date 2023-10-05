<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\RoomService;
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

class ToModeratorWebsocketService
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

    public function newParticipantInLobby(LobbyWaitungUser $lobbyWaitungUser)
    {

        $room = $lobbyWaitungUser->getRoom();
        $title = $this->translator->trans('lobby.notification.newUser.title', ['{name}' => $lobbyWaitungUser->getShowName()]);
        $message = $this->translator->trans(
            'lobby.notification.newUser.message',
            [
                '{name}' => $lobbyWaitungUser->getShowName(),
                '{room}' => $room->getName()
            ]
        );
        $topic = 'lobby_moderator/' . $room->getUidReal();
        // this message goes to the moderators wich are in the lobby
        $this->directSend->sendBrowserNotification($topic, $title, $message, $message, $lobbyWaitungUser->getUid(), 'info',5000);
        sleep(1);

        $messageDashboard = sprintf(
            '%s<br><a href="%s"  class="btn btn-sm btn-primary startIframe" data-roomname="%s">%s</a>',
            $this->translator->trans(
                'lobby.notification.newUser.message',
                [
                    '{name}' => $lobbyWaitungUser->getShowName(),
                    '{room}' => $room->getName()
                ]
            ),
            $this->urlgenerator->generate('room_join', ['room' => $room->getId(), 't' => 'b']),
            $room->getName(),
            $this->translator->trans('lobby.notification.newUser.toLobby')
        );

        //this message goes to the moderators which are not already in the lobby
        foreach ($lobbyWaitungUser->getRoom()->getUserAttributes() as $data) {
            if ($data->getLobbyModerator()) {
                $topic = 'personal/' . $data->getUser()->getUid();
                $this->directSend->sendBrowserNotification($topic, $title, $messageDashboard, $message, $lobbyWaitungUser->getUid(), 'info');
            }
        }
        $topic = 'personal/' . $room->getModerator()->getUid();
        $this->directSend->sendBrowserNotification($topic, $title, $messageDashboard, $message, $lobbyWaitungUser->getUid(), 'info');
    }

    public function refreshLobby(LobbyWaitungUser $lobbyWaitungUser)
    {
        $this->refreshLobbyByRoom($lobbyWaitungUser->getRoom());
    }

    public function refreshLobbyByRoom(Rooms $room)
    {

        $topic = 'lobby_moderator/' . $room->getUidReal();
        $this->directSend->sendRefresh($topic, $this->urlgenerator->generate('lobby_moderator', ['uid' => $room->getUidReal()]) . ' #waitingUser');
    }


    public function participantLeftLobby(LobbyWaitungUser $lobbyWaitungUser)
    {
        $room = $lobbyWaitungUser->getRoom();

        foreach ($lobbyWaitungUser->getRoom()->getUserAttributes() as $data) {
            if ($data->getLobbyModerator()) {
                $topic = 'personal/' . $data->getUser()->getUid();
                $this->directSend->sendCleanBrowserNotification($topic, $lobbyWaitungUser->getUid());
            }
        }
        $topic = 'personal/' . $room->getModerator()->getUid();
        $this->directSend->sendCleanBrowserNotification($topic, $lobbyWaitungUser->getUid());

        $room = $lobbyWaitungUser->getRoom();
        $topic = 'lobby_moderator/' . $room->getUidReal();
        $this->directSend->sendCleanBrowserNotification($topic, $lobbyWaitungUser->getUid());
    }
}
