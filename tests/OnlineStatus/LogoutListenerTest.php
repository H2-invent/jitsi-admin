<?php

namespace App\Tests\OnlineStatus;

use App\Entity\User;
use App\EventListener\LogoutListener;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListenerTest extends TestCase
{
    public function testUserIsMarkedOfflineOnLogout(): void
    {
        $user = new User();
        $user->setOnlineStatus(1);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($user);
        $entityManager->expects(self::once())->method('flush');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        (new LogoutListener($entityManager))(new LogoutEvent(new Request(), $token));

        self::assertSame(0, $user->getOnlineStatus());
    }

    public function testTokenWithoutAppUserIsIgnored(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        (new LogoutListener($entityManager))(new LogoutEvent(new Request(), $token));

        $this->addToAssertionCount(1);
    }

    public function testImpersonatingLogoutMarksOriginalUserOffline(): void
    {
        $admin = new User();
        $admin->setOnlineStatus(1);

        $impersonated = new User();
        $impersonated->setOnlineStatus(1);

        $originalToken = $this->createMock(TokenInterface::class);
        $originalToken->method('getUser')->willReturn($admin);

        $switchUserToken = new SwitchUserToken($impersonated, 'main', [], $originalToken);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with($admin);
        $entityManager->expects(self::once())->method('flush');

        (new LogoutListener($entityManager))(new LogoutEvent(new Request(), $switchUserToken));

        self::assertSame(0, $admin->getOnlineStatus());
        self::assertSame(1, $impersonated->getOnlineStatus());
    }
}
