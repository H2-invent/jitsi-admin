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

class LobbyUpdateService
{
    private $publisher;
    private $urlgenerator;
    private $parameterBag;
    private $logger;

    public function __construct(HubInterface $publisher, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag, LoggerInterface $logger)
    {
        $this->publisher = $publisher;
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
    }

    public function publishLobby(LobbyWaitungUser $lobbyWaitungUser)
    {
        $room = $lobbyWaitungUser->getRoom();
        $user = $lobbyWaitungUser->getUser();
//        try {
        $topic = $this->urlgenerator->generate('lobby_moderator', array('uid' => $room->getUid()), UrlGeneratorInterface::ABSOLUTE_URL);
        $data = array(
            'user' => $user->getFormatedName($this->parameterBag->get('laf_showNameInConference')),
            'createdAt' => $lobbyWaitungUser->getCreatedAt()->format('Y-m-d H:i:s'),
            'reloadUrl'=>$this->urlgenerator->generate('lobby_moderator', array('uid' => $room->getUid())).' #participants'
        );
        $update = new Update($topic, json_encode($data));
        $res = $this->publisher->publish($update);
        return true;
//        }catch (RuntimeException $e){
//            $this->logger->error('Mercure Hub not available: '.$e->getMessage());
//            return false;
//        }

    }
}