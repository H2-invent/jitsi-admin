<?php

namespace App\Service\adhocmeeting;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Tracks the outcome of a web ad-hoc call so the scheduled timeout can tell whether the callee
 * actually answered. Answering removes the CalloutSession, which makes the timeout a no-op.
 * Declining does the same, but additionally tells the caller that the callee refused.
 */
class AdhocCallService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DirectSendService     $directSendService,
    ) {
    }

    public function markAnswered(User $user, Rooms $room): bool
    {
        // Only rooms created ad-hoc can have a waiting web callout. Skipping scheduled,
        // persistent and repeater rooms keeps a normal conference join from querying
        // callout_session at all.
        if ($this->isNonAdhocRoom($room)) {
            return false;
        }

        $calloutSession = $this->findWaitingSession($user, $room);

        if ($calloutSession) {
            $this->entityManager->remove($calloutSession);
            $this->entityManager->flush();
            return true;
        }

        return false;
    }

    /**
     * The callee actively refused the ringing ad-hoc call. This must not be treated as "no
     * answer": the caller has to be told immediately instead of waiting for the timeout.
     */
    public function markDeclined(User $user, Rooms $room): bool
    {
        if ($this->isNonAdhocRoom($room)) {
            return false;
        }

        $calloutSession = $this->findWaitingSession($user, $room);
        if (!$calloutSession) {
            return false;
        }

        $caller = $calloutSession->getInvitedFrom();

        // Remove the session first so the already scheduled timeout message becomes a no-op
        // and the caller (who is listening on adhocCallFailed) is notified exactly once.
        $this->entityManager->remove($calloutSession);
        // End the room so a late join from the still open ringing dialog is rejected.
        $room->setEnddate(new \DateTime());
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        if ($caller) {
            $this->directSendService->sendAdhocCallFailed('personal/' . $caller->getUid(), $room->getId(), 'declined');
        }
        // Dismiss the ringing dialog on other tabs/devices of the callee as well.
        $this->directSendService->sendCloseDialog('personal/' . $user->getUid());

        return true;
    }

    /**
     * The callee's browser reached the end of the configured signaling duration without answering.
     * Driven from the client so the caller is notified and the ringtone stops even when the
     * server-side delayed timeout message was not processed.
     */
    public function markTimedOut(User $callee, Rooms $room): bool
    {
        if ($this->isNonAdhocRoom($room)) {
            return false;
        }

        $calloutSession = $this->findWaitingSession($callee, $room);
        if (!$calloutSession) {
            return false;
        }

        $caller = $calloutSession->getInvitedFrom();

        $this->entityManager->remove($calloutSession);
        // End the room so a late join from the still open ringing dialog is rejected.
        $room->setEnddate(new \DateTime());
        $this->entityManager->persist($room);
        $this->entityManager->flush();

        if ($caller) {
            $this->directSendService->sendAdhocCallFailed('personal/' . $caller->getUid(), $room->getId(), 'timeout');
        }
        // Stop the ringtone on the callee's other tabs/devices as well.
        $this->directSendService->sendCloseDialog('personal/' . $callee->getUid());

        return true;
    }

    private function findWaitingSession(User $user, Rooms $room): ?CalloutSession
    {
        $calloutSession = $this->entityManager->getRepository(CalloutSession::class)
            ->findOneBy(['room' => $room, 'user' => $user]);

        if ($calloutSession && CalloutSession::isWaitingState($calloutSession->getState())) {
            return $calloutSession;
        }

        return null;
    }

    /**
     * The caller left/ended the ad-hoc call before the callee answered. All still ringing calls
     * that this user started are ended so the callee's dialog (and ringtone) stops instead of
     * waiting for the timeout.
     *
     * @return int number of cancelled callouts
     */
    public function cancelPendingCallsByInviter(User $caller): int
    {
        $sessions = $this->entityManager->getRepository(CalloutSession::class)
            ->findBy(['invitedFrom' => $caller]);

        $cancelled = 0;
        foreach ($sessions as $session) {
            if (!CalloutSession::isWaitingState($session->getState())) {
                continue;
            }

            $callee = $session->getUser();
            $room = $session->getRoom();

            $this->entityManager->remove($session);
            // End the room so a late join from the still open ringing dialog is rejected.
            if ($room) {
                $room->setEnddate(new \DateTime());
                $this->entityManager->persist($room);
            }
            if ($callee) {
                // Stops the ringing dialog and its ringtone on the callee side.
                $this->directSendService->sendCloseDialog('personal/' . $callee->getUid());
            }
            $cancelled++;
        }

        if ($cancelled > 0) {
            $this->entityManager->flush();
        }

        return $cancelled;
    }

    private function isNonAdhocRoom(Rooms $room): bool
    {
        // Scheduled, persistent and repeater rooms never have a web ad-hoc callout.
        return $room->getScheduleMeeting() || $room->getPersistantRoom() || $room->getRepeater();
    }
}
