<?php

namespace App\Service\Callout;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\LobbyWaitungUserRepository;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CalloutService
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private AdhocMeetingService        $adhocMeetingService,
        private ThemeService               $themeService,
        private LoggerInterface            $logger,
        private LobbyWaitungUserRepository $lobbyWaitungUserRepository,
    )
    {
    }

    /**
     * Starts the Callout Session Process.
     * @param Rooms $rooms
     * @param User $user
     * @param User $inviter
     * @return CalloutSession|null
     */
    public function initCalloutSession(Rooms $rooms, User $user, User $inviter): ?CalloutSession
    {
        return $this->createCallout($rooms, $user, $inviter);
    }

    /**
     * Creates a new CalloutSession and rings the calles user, if this user online and propably not a phone user
     * @param Rooms $rooms
     * @param User $user
     * @param User $inviter
     * @return CalloutSession|null
     */
    public function createCallout(Rooms $rooms, User $user, User $inviter): ?CalloutSession
    {
        $callout = $this->checkCallout($rooms, $user);
        if ($inviter === $user) {
            return null;
        }

        if ($callout) {
            if ($callout->getState() > 1) {//calloutsession is on hold
                if ($callout->getLeftRetries() > 0) {
                    $callout->setLeftRetries($callout->getLeftRetries() - 1);
                    $callout->setState(CalloutSession::$INITIATED);
                    $this->adhocMeetingService->sendAddhocMeetingWebsocket($user, $inviter, $rooms);
                    $this->entityManager->persist($callout);
                    $this->entityManager->flush();
                }
            }
            return $callout;
        }

        $this->adhocMeetingService->sendAddhocMeetingWebsocket($user, $inviter, $rooms);

        if (!$this->isAllowedToBeCalled($user)) {
            return null;
        }
        if ($this->isalreadyInTheConfernce(user: $user)){
            return null;
        }

        $callout = new CalloutSession();
        $callout->setUser($user)
            ->setRoom($rooms)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($inviter)
            ->setUid(md5(uniqid()))
            ->setState(CalloutSession::$INITIATED)
            ->setLeftRetries($this->themeService->getApplicationProperties('CALLOUT_MAX_RETRIES'));
        $this->entityManager->persist($callout);
        $this->entityManager->flush();

        return $callout;
    }

    /**
     * checks is the callout session is already astablished
     * @param Rooms $rooms
     * @param User $user
     * @return CalloutSession|null
     */
    public function checkCallout(Rooms $rooms, User $user): ?CalloutSession
    {
        return $this->entityManager->getRepository(CalloutSession::class)->findOneBy(['room' => $rooms, 'user' => $user]);
    }

    /**
     * chechks either the user is alles to be called. is is done by check the env variable with the LDAP user properties
     * and the corresponding spezial fields, which are loaded from the ldap
     * @param User|null $user
     * @return bool
     */
    public function isAllowedToBeCalled(?User $user): bool
    {
        return $this->getCallerIdForUser($user) !== null;
    }

    /**
     * checks if the user is already invited or is already in the lobby
     * @param User|null $user
     * @return bool
     */
    public function isalreadyInTheConfernce(?User $user): bool
    {
        $lobbyUser = $this->lobbyWaitungUserRepository->findOneBy(array('user' => $user));
        if ($lobbyUser) {
            return true;
        }
        return false;
    }

    /**
     * Returns the CallerID which is mostly the telefonnumber from a user if this is configured
     * @param User|null $user
     * @return mixed|null
     */
    public function getCallerIdForUser(?User $user)
    {
        if (!$user) {
            return null;
        }
        if (!$user->getLdapUserProperties()) {
            return null;
        }

        $calloutFields = $this->themeService->getApplicationProperties('LDAP_CALLOUT_FIELDS');
        foreach ($calloutFields as $ldapId => $fields) {
            foreach ($fields as $field) {
                if ($user->getLdapUserProperties()->getLdapNumber() === $ldapId) {
                    if (isset($user->getSpezialProperties()[$field]) && $user->getSpezialProperties()[$field] !== '') {
                        return $user->getSpezialProperties()[$field];
                    }
                }
            }
        }
        return null;
    }

    public function dialSuccessfull(User $user, Rooms $rooms): bool
    {
//        $calloutRepo = $this->entityManager->getRepository(CalloutSession::class);
//        $calloutSession = $calloutRepo->findOneBy(array('room' => $rooms, 'user' => $user));
//
//        if ($calloutSession) {
//            $calloutSession = $calloutRepo->findCalloutSessionActive($calloutSession->getUid());
//            if ($calloutSession) {
//                $this->entityManager->remove($calloutSession);
//                $this->entityManager->flush();
//                $this->logger->debug('The Calloutsession was destoyed Successfully');
//                return true;
//            }
//            $this->logger->debug('There is no valid Callout Session which can be destroyed. The Calloutsession is not in the right state');
//        }else{
//            $this->logger->debug('There is no calloutsession with this user and room');
//        }
//        return false;
    }
}
