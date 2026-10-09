<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class LogoutListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $token = $event->getToken();

        // Under switch_user the stored token belongs to the impersonated user; the account that
        // actually logs out is the original one.
        $user = $token instanceof SwitchUserToken ? $token->getOriginalToken()->getUser() : $token?->getUser();
        if (!$user instanceof User) {
            return;
        }

        // online_status is a per-account manual override, not per session: logging out of any
        // session marks the account offline for ad-hoc calls. Live websocket presence stays the
        // per-session signal.
        $user->setOnlineStatus(0);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
