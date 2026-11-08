<?php

declare(strict_types=1);

namespace App\Tests\OutlookPlugin;

use App\Controller\OutlookPluginLoginController;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


final class OutlookPluginLoginControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private OutlookPluginLoginController $controller;
    private Container $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->controller = new OutlookPluginLoginController($this->entityManager);
        $this->container = new Container();
        $this->container->set('security.token_storage', new TokenStorage());
        $this->controller->setContainer($this->container);
    }

    public function testLoginCreatesAndPersistsToken(): void
    {
        $user = $this->user(null);
        $this->authenticate($user);
        $this->expectTokenLookups([null]);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $response = $this->controller->login();

        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{32}$/',
            $user->getOutlookPluginToken(),
        );

        $this->assertJsonResponse([
            'token' => $user->getOutlookPluginToken(),
            'name' => 'Erika Musterfrau',
        ], $response);
    }

    public function testLoginReplacesExistingToken(): void
    {
        $user = $this->user('existing-token');
        $this->authenticate($user);
        $this->expectTokenLookups([null]);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $response = $this->controller->login();

        self::assertNotSame('existing-token', $user->getOutlookPluginToken());
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{32}$/',
            $user->getOutlookPluginToken(),
        );

        $this->assertJsonResponse([
            'token' => $user->getOutlookPluginToken(),
            'name' => 'Erika Musterfrau',
        ], $response);
    }

    public function testLoginRetriesWhenTokenAlreadyExists(): void
    {
        $user = $this->user(null);
        $this->authenticate($user);

        // First Round colission.  second round: free user token.
        $this->expectTokenLookups([$this->user('occupied-token'), null]);

        $this->entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($user));

        $this->entityManager->expects(self::once())
            ->method('flush');

        $response = $this->controller->login();

        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{32}$/',
            $user->getOutlookPluginToken(),
        );

        $this->assertJsonResponse([
            'token' => $user->getOutlookPluginToken(),
            'name' => 'Erika Musterfrau',
        ], $response);
    }

    public function testLoginRejectsMissingAuthenticatedUser(): void
    {
        // TokenStorage ist accesable, but contains no token.
        $this->expectNoWrites();
        $this->entityManager->expects(self::never())
            ->method('getRepository');

        $this->assertJsonResponse([
            'error' => true,
            'reason' => 'No user loged in',
        ], $this->controller->login());
    }

    public function testLoginRouteCombinesClassAndMethodAttributes(): void
    {
        $reflection = new \ReflectionClass(OutlookPluginLoginController::class);
        $classAttributes = $reflection->getAttributes(Route::class);

        self::assertCount(1, $classAttributes);

        $prefix = $classAttributes[0]->newInstance();

        self::assertSame('/room/outlook/plugin', $prefix->getPath());
        self::assertSame('app_outlook_plugin', $prefix->getName());

        $attributes = $reflection->getMethod('login')->getAttributes(Route::class);

        self::assertCount(1, $attributes);

        $route = $attributes[0]->newInstance();

        self::assertSame('/outlooklogin', $route->getPath());
        self::assertSame('login', $route->getName());
        self::assertSame(['GET'], $route->getMethods());
        self::assertSame(
            '/room/outlook/plugin/outlooklogin',
            $prefix->getPath() . $route->getPath(),
        );
        self::assertSame(
            'app_outlook_pluginlogin',
            $prefix->getName() . $route->getName(),
        );
    }

    /**
     * @param list<User|null> $results
     */
    private function expectTokenLookups(array $results): void
    {
        $repository = $this->createMock(EntityRepository::class);

        $repository->expects(self::exactly(count($results)))
            ->method('findOneBy')
            ->with(self::callback(static function (array $criteria): bool {
                self::assertSame(
                    ['outlookPluginToken'],
                    array_keys($criteria),
                );

                self::assertIsString($criteria['outlookPluginToken']);
                self::assertMatchesRegularExpression(
                    '/^[a-f0-9]{32}$/',
                    $criteria['outlookPluginToken'],
                );

                return true;
            }))
            ->willReturnOnConsecutiveCalls(...$results);

        $this->entityManager->expects(self::exactly(count($results)))
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);
    }

    private function user(?string $token): User
    {

        $user = $this->createStub(User::class);
        $user->method('getUid')->willReturn('test-user');
        $user->method('getFirstName')->willReturn('Erika');
        $user->method('getLastName')->willReturn('Musterfrau');

        $user->method('getOutlookPluginToken')->willReturnCallback(
            static function () use (&$token): ?string {
                return $token;
            },
        );

        $user->method('setOutlookPluginToken')->willReturnCallback(
            static function (?string $value) use (&$token, $user): User {
                $token = $value;

                return $user;
            },
        );

        return $user;
    }

    private function authenticate(User $user): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $storage = new TokenStorage();
        $storage->setToken($token);

        $this->container->set('security.token_storage', $storage);
    }

    private function expectNoWrites(): void
    {
        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('flush');
    }

    private function assertJsonResponse(array $expected, Response $response): void
    {

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(
            'application/json',
            $response->headers->get('Content-Type'),
        );
        self::assertSame(
            $expected,
            json_decode(
                (string) $response->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        );
    }
}
