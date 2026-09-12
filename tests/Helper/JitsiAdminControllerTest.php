<?php

namespace App\Tests\Helper;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Repository\UserRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Contracts\Translation\TranslatorInterface;

class JitsiAdminControllerTest extends KernelTestCase
{
    private function createController(): JitsiAdminController
    {
        self::bootKernel();
        $container = self::getContainer();

        $controller = new JitsiAdminController(
            $container->get(ManagerRegistry::class),
            $container->get(TranslatorInterface::class),
            $container->get(LoggerInterface::class),
            $container->get(ParameterBagInterface::class),
        );
        $controller->setContainer($container);

        return $controller;
    }

    private function invokeGetSessionUser(JitsiAdminController $controller, Session $session): ?User
    {
        $method = new \ReflectionMethod($controller, 'getSessionUser');
        $method->setAccessible(true);

        return $method->invoke($controller, $session);
    }

    public function testGetLoggerReturnsTheInjectedLogger(): void
    {
        $controller = $this->createController();

        $this->assertSame(self::getContainer()->get(LoggerInterface::class), $controller->getLogger());
    }

    public function testGetDoctrineReturnsTheInjectedManagerRegistry(): void
    {
        $controller = $this->createController();

        $this->assertSame(self::getContainer()->get(ManagerRegistry::class), $controller->getDoctrine());
    }

    public function testGetSessionUserReturnsTheAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'main', []));

        $result = $this->invokeGetSessionUser($controller, new Session(new MockArraySessionStorage()));

        $this->assertSame($user, $result);
    }

    public function testGetSessionUserFallsBackToTheSessionUserId(): void
    {
        $controller = $this->createController();
        self::getContainer()->get('security.token_storage')->setToken(null);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);

        $session = new Session(new MockArraySessionStorage());
        $session->set('userId', $user->getId());

        $result = $this->invokeGetSessionUser($controller, $session);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($user->getId(), $result->getId());
    }

    public function testGetSessionUserReturnsNullWhenSessionUserDoesNotExist(): void
    {
        $controller = $this->createController();
        self::getContainer()->get('security.token_storage')->setToken(null);

        $session = new Session(new MockArraySessionStorage());
        $session->set('userId', 999999999);

        $this->assertNull($this->invokeGetSessionUser($controller, $session));
    }
}
