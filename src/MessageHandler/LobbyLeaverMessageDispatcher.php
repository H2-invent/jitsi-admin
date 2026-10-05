<?php

namespace App\MessageHandler;

use App\Entity\LobbyWaitungUser;
use App\Message\LobbyLeaverMessage;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LobbyLeaverMessageDispatcher
{
    public function __construct(private readonly LoggerInterface             $logger, private readonly ToModeratorWebsocketService $toModerator, private readonly EntityManagerInterface      $em)
    {
    }

    public function __invoke(LobbyLeaverMessage $lobbyLeaverMessage): void
    {
        $lobbyWaitingUSer = $this->em->getRepository(LobbyWaitungUser::class)->findOneBy(['uid' => $lobbyLeaverMessage->getId()]);


        if ($lobbyWaitingUSer) {
            $this->em->refresh($lobbyWaitingUSer);
            $this->logger->info("Start clean the Lobby User");
            if ($lobbyWaitingUSer->getCloseBrowser() === true) {
                $this->logger->info("The Browser was not refreshed so we clean the  lobbyuser");
                $this->em->remove($lobbyWaitingUSer);
                $this->em->flush();
                $this->toModerator->refreshLobby($lobbyWaitingUSer);
                $this->toModerator->participantLeftLobby($lobbyWaitingUSer);
                return;
            }
            $this->logger->info("The Browser was refreshed");
        } else {
            $this->logger->info('The USer already left the Lobby or is not existing anymore');
        }
    }
}
