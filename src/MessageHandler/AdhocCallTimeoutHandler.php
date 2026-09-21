<?php

namespace App\MessageHandler;

use App\Entity\CalloutSession;
use App\Message\AdhocCallTimeoutMessage;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Fires after ADHOC_CALL_SIGNALING_DURATION seconds. If the callee has not answered in the
 * meantime, the caller is told the call failed, the callee's ringing dialog is closed and the
 * call is ended.
 */
#[AsMessageHandler]
class AdhocCallTimeoutHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DirectSendService     $directSendService,
        private LoggerInterface        $logger,
    ) {
    }

    public function __invoke(AdhocCallTimeoutMessage $message): void
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)
            ->findOneBy(['uid' => $message->getCalloutSessionUid()]);

        // The session is removed when the callee answers (or the call is cancelled), so a missing
        // session means the call succeeded and there is nothing to report.
        if (!$calloutSession) {
            $this->logger->debug('Ad-hoc call timeout: session already finished', ['uid' => $message->getCalloutSessionUid()]);
            return;
        }

        if (!CalloutSession::isWaitingState($calloutSession->getState())) {
            $this->logger->debug('Ad-hoc call timeout: session is no longer waiting', ['uid' => $message->getCalloutSessionUid(), 'state' => $calloutSession->getState()]);
            return;
        }

        $room = $calloutSession->getRoom();
        $caller = $calloutSession->getInvitedFrom();
        $callee = $calloutSession->getUser();

        $calloutSession->setState(CalloutSession::$TIMEOUT);
        // End the call: an expired room can no longer be joined, so a late accept is rejected.
        $room->setEnddate(new \DateTime());
        $this->entityManager->persist($calloutSession);
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        if ($caller) {
            $this->directSendService->sendAdhocCallFailed('personal/' . $caller->getUid(), $room->getId());
        }
        if ($callee) {
            $this->directSendService->sendCloseDialog('personal/' . $callee->getUid());
        }
    }
}
