<?php

namespace App\Service\Callout;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use App\Service\RoomAddService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This Calls contains functions when a callout session is removed
 */
class CallOutSessionAPIRemoveService
{
    public function __construct(
        private EntityManagerInterface      $entityManager,
        private ToModeratorWebsocketService $toModeratorWebsocketService,
        private RoomAddService              $roomAddService,
        private DirectSendService           $directSendService,
        private TranslatorInterface         $translator,
        private ThemeService                $themeService,
    )
    {
    }

    /**
     * @param $sessionId
     * @return array
     * the user refuse the call
     * the session is removed and a message is send to the lobbymoderator
     */
    public function refuse($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return ['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'];
        }
        return $this->removeCalloutSession(
            $calloutSession,
            $this->translator->trans(
                'callout.message.refuse',
                ['name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))]
            )
        );
    }

    /**
     * @param $sessionId
     * @return array
     * An error occurred during calling a invited participant
     */
    public function error($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return ['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'];
        }
        return $this->removeCalloutSession(
            $calloutSession,
            $this->translator->trans(
                'callout.message.error',
                ['name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))]
            )
        );
    }

    /**
     * @param $sessionId
     * @return array
     * The phone is not reachable.
     * The inviter is informed about the unreachable of the invited phone
     * The difference between error and unreachable is only the message which is send to the lobbymoderator
     */
    public function unreachable($sessionId): array
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)->findCalloutSessionActive($sessionId);
        if (!$calloutSession) {
            return ['error' => true, 'reason' => 'NO_SESSION_ID_FOUND'];
        }
        return $this->removeCalloutSession(
            $calloutSession,
            $this->translator->trans(
                'callout.message.unreachable',
                ['name' => $calloutSession->getUser()->getFormatedName($this->themeService->getApplicationProperties('laf_showNameFrontend'))]
            )
        );
    }


    /**
     * @param CalloutSession|null $calloutSession
     * @param $message
     * @return array
     * This is a generic function to remove the callout session
     */
    public function removeCalloutSession(?CalloutSession $calloutSession, $message)
    {

        $this->entityManager->remove($calloutSession);
        $this->entityManager->flush();
        $this->toModeratorWebsocketService->refreshLobbyByRoom($calloutSession->getRoom());
        $this->sendRefuseMessage($calloutSession->getRoom(), $message);
        $this->roomAddService->removeUserFromRoomNoRepeat($calloutSession->getRoom(), $calloutSession->getUser());
        $res = [
            'status' => 'DELETED',
            'links' => []
        ];
        return $res;
    }

    /**
     * @param Rooms $room
     * @param $message
     * @return void
     * This function sends a refuse message to the lobbymoderator
     */
    public function sendRefuseMessage(Rooms $room, $message)
    {
        $topic = 'lobby_moderator/' . $room->getUidReal();
        $this->directSendService->sendSnackbar($topic, $message, 'danger',2000);
    }
}
