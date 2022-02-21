<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
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
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
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
    private $uploadHelper;
    public function __construct(UploaderHelper $uploaderHelper, DirectSendService $directSendService, Environment $environment, HubInterface $publisher, RoomService $roomService, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->publisher = $publisher;
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->roomService = $roomService;
        $this->twig = $environment;
        $this->directSend = $directSendService;
        $this->uploadHelper = $uploaderHelper;
    }

    public function setDirectSend(DirectSendService $directSendService)
    {
        $this->directSend = $directSendService;
    }

    public function acceptLobbyUser(LobbyWaitungUser $lobbyWaitungUser)
    {

        $topic = 'lobby_WaitingUser_websocket/'.$lobbyWaitungUser->getUid();
        $this->directSend->sendSnackbar($topic, $this->translator->trans('lobby.participant.accept'), 'success');
        $appUrl = $this->roomService->join(
            $lobbyWaitungUser->getRoom(),
            $lobbyWaitungUser->getUser(),
            'a',
            $lobbyWaitungUser->getShowName()
        );

        if ($lobbyWaitungUser->getType() === 'b') {
            $options = array(
                'options' => array(
                    'jwt' => $this->roomService->generateJwt($lobbyWaitungUser->getRoom(), $lobbyWaitungUser->getUser(), $lobbyWaitungUser->getShowName()),
                    'roomName' => $lobbyWaitungUser->getRoom()->getUid(),
                    'width' => '100%',
                    'height' => 400,
                ),
                'roomName' => $lobbyWaitungUser->getRoom()->getName(),
                'domain' => $lobbyWaitungUser->getRoom()->getServer()->getUrl(),
                'parentNode' => '#jitsiWindow',
                'userInfo'=>array(
                    'displayName'=>$lobbyWaitungUser->getShowName()),
            );
            if($lobbyWaitungUser->getUser() && $lobbyWaitungUser->getUser()->getProfilePicture()){
                $options['userInfo']['avatarUrl']=  $this->uploadHelper->asset($lobbyWaitungUser->getUser()->getProfilePicture(),'documentFile');
            }
            $this->directSend->sendNewJitsiMeeting($topic, $options, 5000);
        } elseif ($lobbyWaitungUser->getType() === 'a') {
            $this->directSend->sendRedirect($topic, $appUrl, 5000);
            $this->directSend->sendRedirect($topic, '/', 6000);
        }
    }

    public function sendDecline(LobbyWaitungUser $lobbyWaitungUser)
    {
        $topic = 'lobby_WaitingUser_websocket/'.$lobbyWaitungUser->getUid();
        $this->directSend->sendSnackbar($topic,$this->translator->trans('lobby.participant.decline'),'danger');
        $this->directSend->sendRedirect($topic, $this->urlgenerator->generate('index'), $this->parameterBag->get('laf_lobby_popUpDuration'));
    }
}