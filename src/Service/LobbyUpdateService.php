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

class LobbyUpdateService
{
    private $publisher;
    private $urlgenerator;
    private $parameterBag;
    private $logger;
    private $translator;
    private $roomService;
    private $twig;

    public function __construct(Environment $environment, HubInterface $publisher, RoomService $roomService, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->publisher = $publisher;
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->roomService = $roomService;
        $this->twig = $environment;
    }

    public function newParticipantInLobby(LobbyWaitungUser $lobbyWaitungUser)
    {
        $room = $lobbyWaitungUser->getRoom();
        $user = $lobbyWaitungUser->getUser();
        try {
            $topic = $this->urlgenerator->generate('lobby_moderator', array('uid' => $room->getUid()), UrlGeneratorInterface::ABSOLUTE_URL);
            $data = array(
                'type' => 'notification',
                'user' => $user->getFormatedName($this->parameterBag->get('laf_showNameInConference')),
                'createdAt' => $lobbyWaitungUser->getCreatedAt()->format('Y-m-d H:i:s'),
                'title' => $this->translator->trans('lobby.notification.newUser.title', array('{name}' => $user->getFormatedName($this->parameterBag->get('laf_showNameInConference')))),
                'message' => $this->translator->trans('lobby.notification.newUser.message', array(
                    '{name}' => $user->getFormatedName($this->parameterBag->get('laf_showNameInConference')),
                    '{room}' => $room->getName()
                ))
            );
            $update = new Update($topic, json_encode($data));
            $res = $this->publisher->publish($update);
            return true;
        } catch (RuntimeException $e) {
            $this->logger->error('Mercure Hub not available: ' . $e->getMessage());
            return false;
        }


    }

    public function refreshLobby(LobbyWaitungUser $lobbyWaitungUser)
    {
        $room = $lobbyWaitungUser->getRoom();
        $user = $lobbyWaitungUser->getUser();
        try {
            $topic = $this->urlgenerator->generate('lobby_moderator', array('uid' => $room->getUid()), UrlGeneratorInterface::ABSOLUTE_URL);
            $data = array(
                'type' => 'refresh',
                'reloadUrl' => $this->urlgenerator->generate('lobby_moderator', array('uid' => $room->getUid())) . ' #waitingUser',
            );
            $update = new Update($topic, json_encode($data));
            $res = $this->publisher->publish($update);
            return true;
        } catch (RuntimeException $e) {
            $this->logger->error('Mercure Hub not available: ' . $e->getMessage());
            return false;
        }
    }

    public function acceptLobbyUser(LobbyWaitungUser $lobbyWaitungUser)
    {
//        try {
        $topic = $this->urlgenerator->generate('lobby_participants_wait', array('roomUid' => $lobbyWaitungUser->getRoom()->getUid(), 'userUid' => $lobbyWaitungUser->getUser()->getUid()), UrlGeneratorInterface::ABSOLUTE_URL);
        if ($this->parameterBag->get('start_dropdown_allow_browser') == 1 && $this->parameterBag->get('start_dropdown_allow_app') == 1) {

            $data = $this->buildModal(
                $this->twig->render('lobby_participants/choose.html.twig',
                    array('appUrl' => $this->roomService->join(
                        $lobbyWaitungUser->getRoom(),
                        $lobbyWaitungUser->getUser(),
                        'a',
                        $lobbyWaitungUser->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference'))
                    ),
                        'browserUrl' => $this->roomService->join(
                            $lobbyWaitungUser->getRoom(),
                            $lobbyWaitungUser->getUser(),
                            'b',
                            $lobbyWaitungUser->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference'))
                        )
                    )
                )
            );

        } elseif ($this->parameterBag->get('start_dropdown_allow_browser') == 1) {
            $data = $this->buildRedirect($this->roomService->join($lobbyWaitungUser->getRoom(), $lobbyWaitungUser->getUser(), 'b', $lobbyWaitungUser->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference'))));

        } elseif ($this->parameterBag->get('start_dropdown_allow_app') == 1) {
            $data = $this->buildRedirect($this->roomService->join($lobbyWaitungUser->getRoom(), $lobbyWaitungUser->getUser(), 'a', $lobbyWaitungUser->getUser()->getFormatedName($this->parameterBag->get('laf_showNameInConference'))));

        }

        $update = new Update($topic, json_encode($data));
        $res = $this->publisher->publish($update);
        return true;
//        } catch (RuntimeException $e) {
//            $this->logger->error('Mercure Hub not available: ' . $e->getMessage());
//            return false;
//        }
    }

    private function buildModal($content)
    {
        $res = array(
            'type' => 'modal',
            'content' => $content,

        );
        return $res;
    }

    private function buildRedirect($url)
    {
        $res = array(
            'type' => 'redirect',
            'url' => $url,
            'message' => $this->translator->trans('lobby.participant.accept'),
            'timeout' => 1000,
        );
        return $res;
    }
}