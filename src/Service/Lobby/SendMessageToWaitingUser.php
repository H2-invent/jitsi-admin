<?php

namespace App\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\PredefinedLobbyMessages;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\ThemeService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class SendMessageToWaitingUser
{
    private $isAllowedToCreateCustom;

    public function __construct(
        private EntityManagerInterface        $entityManager,
        private ToParticipantWebsocketService $toParticipantWebsocketService,
        private ThemeService                  $themeService,
        private LoggerInterface               $logger,
    )
    {
        $this->isAllowedToCreateCustom = $this->themeService->getApplicationProperties('LAF_LOBBY_ALLOW_CUSTOM_MESSAGES');
    }

    public function sendMessageToAllWaitingUser($message, User $user, Rooms $rooms): array
    {
        $counter = 0;
        $success = true;
        foreach ($rooms->getLobbyWaitungUsers() as $data) {
            if ($this->sendMessage($data->getUid(), $message, $user) === true) {
                $counter++;
            } else {
                $success = false;
            };
        }
        return ['counter' => $counter, 'success' => $success];
    }

    public function sendMessage($uid, $message, User $user): bool
    {
        $waitingUser = $this->entityManager->getRepository(LobbyWaitungUser::class)->findOneBy(['uid' => $uid]);
        if (!$waitingUser) {
            $this->logger->error('NO user found for uid', ['uid' => $uid]);
            return false;
        }
        if (UtilsHelper::isAllowedToOrganizeLobby($user, $waitingUser->getRoom())) {
            if (is_int($message)) {
                $this->logger->debug('Send Message from id', ['id' => $message]);
                $res = $this->createMesagefromId($message);
            } else {
                $this->logger->debug('Send Message from string', ['id' => $message]);
                $res = $this->createMessageFromString($message, $this->isAllowedToCreateCustom);
            }
            if ($res) {
                $this->logger->debug('Send Message via websocket', ['uid' => $waitingUser->getUid(), 'message' => $res]);
                if ($waitingUser->getCallerSession()) {
                    $this->logger->debug('The Waitunguser is from a callersession', ['calleruid' => $waitingUser->getCallerSession()->getId()]);
                    $callerSession = $waitingUser->getCallerSession();
                    $callerSession->setMessageUid(messageUid: md5(uniqid()));
                    $callerSession->setMessageText(messageText: $res);
                    $this->entityManager->persist($callerSession);
                    $this->entityManager->flush();
                }
                $this->toParticipantWebsocketService->sendMessage($waitingUser, $res, $user->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend')));
            }

            return (bool)$res;
        } else {
            $this->logger->error('USer tried to send message where he has no acess to', ['USer-uid' => $user->getUsername()]);
            return false;
        }
    }

    public function createMesagefromId($id): ?string
    {
        $message = $this->entityManager->getRepository(PredefinedLobbyMessages::class)->findOneBy(['id' => $id, 'active' => true]);
        if (!$message) {
            $this->logger->debug('Fetch message from id', ['message' => $id]);
            return null;
        }
        $this->logger->debug('Fetch message from id', ['message' => $message->getText()]);
        return $message->getText();
    }

    public function createMessageFromString($message, int $allowCreating): ?string
    {
        if ($allowCreating === 1) {
            $this->logger->debug('We create a custom message from a string');
            return $message;
        }
        $this->logger->debug('No custom messages are allowed');
        return null;
    }
}
