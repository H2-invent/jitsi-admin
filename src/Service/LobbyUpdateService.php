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
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

class LobbyUpdateService
{
    private $publisher;
    private $urlgenerator;
    private $parameterBag;
    private $logger;
    private $translator;

    public function __construct(HubInterface $publisher, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->publisher = $publisher;
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->translator = $translator;
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
                'title' => $this->translator->trans('{name} ist in der Lobby', array('{name}' => $user->getFormatedName($this->parameterBag->get('laf_showNameInConference')))),
                'message' => $this->translator->trans('Der Teilnehmer {name} ist der Lobby der Konferenz {room} beigetreten', array(
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
}