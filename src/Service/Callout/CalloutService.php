<?php

namespace App\Service\Callout;

use App\Entity\CallerSession;
use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\LobbyWaitungUserRepository;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\adhocmeeting\AdhocMeetingWebsocketService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class CalloutService
{
    public function __construct(
        private EntityManagerInterface       $entityManager,
        private ThemeService                 $themeService,
        private LobbyWaitungUserRepository   $lobbyWaitungUserRepository,
        private AdhocMeetingWebsocketService $adhocMeetingWebsocketService,
        private LoggerInterface              $logger,
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
    public
    function initCalloutSession(Rooms $rooms, User $user, User $inviter): ?CalloutSession
    {

        $this->logger->debug('create callout session');
        return $this->createCallout($rooms, $user, $inviter);
    }

    /**
     * Creates a new CalloutSession and rings the calles user, if this user online and propably not a phone user
     * @param Rooms $rooms
     * @param User $user
     * @param User $inviter
     * @return CalloutSession|null
     */
    public
    function createCallout(Rooms $rooms, User $user, User $inviter): ?CalloutSession
    {
        $callout = $this->checkCallout($rooms, $user);
        $callIn = $this->checkCallIn($rooms, $user);
        if ($inviter === $user) {
            $this->logger->debug('no inviter found to invite into callout. Leave callout invitation');
            return null;
        }
        if ($callIn) {
            $this->logger->debug('the invited user has already a calling Session. So it is not allowed to retry a callout');
            return null;
        }

        if ($callout) {

            $this->logger->debug('there is already a calloutsession. Change retries und reinvite the callout user');
            if ($callout->getState() > 1) {//calloutsession is on hold
                $this->logger->debug('The callout session is on hold an it is tried to recall the user');
                if ($callout->getLeftRetries() > 0) {
                    $this->logger->debug('The callout session is on hold and there are left retries so we call the user again');
                    $callout->setLeftRetries($callout->getLeftRetries() - 1);
                    $callout->setState(CalloutSession::$INITIATED);
                    $this->adhocMeetingWebsocketService->sendAddhocMeetingWebsocket($user, $inviter, $rooms);
                    $this->entityManager->persist($callout);
                    $this->entityManager->flush();
                }
            }
            return $callout;
        }
        $this->logger->debug('Send Callout message to websocket so the called user is invited');
        $this->adhocMeetingWebsocketService->sendAddhocMeetingWebsocket($user, $inviter, $rooms);

        if (!$this->isAllowedToBeCalled($user)) {
            $this->logger->debug('The USer is not allowed to be called');

            return null;
        }
        if ($this->isalreadyInTheConfernce(user: $user,rooms: $rooms)) {
            $this->logger->debug('The User was already invied in the conference conference');
            return null;
        }

        $this->logger->debug('there is no calloutsession. So we create a new one');
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
    public
    function checkCallout(Rooms $rooms, User $user): ?CalloutSession
    {
        $this->logger->debug('check if callout exists');
        return $this->entityManager->getRepository(CalloutSession::class)->findOneBy(['room' => $rooms, 'user' => $user]);
    }

    /**
     * checks if a callInSession is running
     * @param Rooms $rooms
     * @param User $user
     * @return CalloutSession|null
     */
    public function checkCallIn(Rooms $rooms, User $user): ?CallerSession
    {
        $this->logger->debug('check if callin exists');
        return $this->entityManager->getRepository(CallerSession::class)->findCallerSessionByUserAndRoom($user, $rooms);
    }

    /**
     * chechks either the user is alles to be called. is is done by check the env variable with the LDAP user properties
     * and the corresponding spezial fields, which are loaded from the ldap
     * @param User|null $user
     * @return bool
     */
    public
    function isAllowedToBeCalled(?User $user): bool
    {
        return $this->getCallerIdForUser($user) !== null;
    }

    /**
     * checks if the user is already invited or is already in the lobby
     * @param User|null $user
     * @return bool
     */
    public
    function isalreadyInTheConfernce(?User $user, ?Rooms $rooms): bool
    {
        $lobbyUser = $this->lobbyWaitungUserRepository->findOneBy(array('user' => $user, 'room'=>$rooms));
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
    public
    function getCallerIdForUser(?User $user)
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

}
