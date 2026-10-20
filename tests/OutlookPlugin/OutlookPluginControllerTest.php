<?php

declare(strict_types=1);

namespace App\Tests\OutlookPlugin;

use App\Controller\OutlookPluginController;
use App\Entity\Server;
use App\Entity\User;
use App\Service\CreateHttpsUrl;
use App\Service\PublicConference\PublicConferenceService;
use App\Service\ServerUserManagment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Ablage: tests/Controller/OutlookPluginControllerTest.php
 * Ziel: PHPUnit 10/11, ohne Kernel und Datenbank.
 * Tokenaufrufe senden Authorization: Bearer api-token; die Repository-Mocks
 * erwarten api-token ohne Praefix und pruefen damit dessen Entfernung.
 * Voraussetzungen: gemockte Klassen/Methoden sind nicht final;
 * getServersFromUser() liefert ein Array (wie vom Controller vorausgesetzt).
 * Der Raumtyp wird aus dem deklarierten Service-Rueckgabetyp ermittelt.
 * Falls dieser fehlt, createRoomMock() an die konkrete Raumklasse anpassen.
 * Die Route-Attribute werden separat geprueft. HTTP-Routing, Firewall und
 * eine tatsaechliche 405-Antwort benoetigen zusaetzliche funktionale Tests.
 * Hier mangels PHP und Projektabhaengigkeiten nicht ausgefuehrt.
 */
final class OutlookPluginControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ServerUserManagment&MockObject $serverManagement;
    private PublicConferenceService&MockObject $conferenceService;
    private CreateHttpsUrl&MockObject $httpsUrl;
    private OutlookPluginController $controller;
    private Container $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->serverManagement = $this->createMock(ServerUserManagment::class);
        $this->conferenceService = $this->createMock(PublicConferenceService::class);
        $this->httpsUrl = $this->createMock(CreateHttpsUrl::class);

        // Der finale Controller wird real instanziiert, nicht gemockt.
        $this->controller = new OutlookPluginController(
            $this->entityManager,
            $this->serverManagement,
            $this->conferenceService,
            $this->httpsUrl,
        );
        $this->container = new Container();
        $this->container->set('security.token_storage', new TokenStorage());
        $this->controller->setContainer($this->container);
    }

    public function testLoginCreatesAndPersistsToken(): void
    {
        $user = $this->user(null);
        $this->authenticate($user);
        $this->entityManager->expects(self::once())->method('persist')
            ->with(self::identicalTo($user));
        $this->entityManager->expects(self::once())->method('flush');

        $response = $this->controller->login();

        self::assertNotEmpty($user->getOutlookPluginToken());
        $this->assertJsonResponse([
            'token' => $user->getOutlookPluginToken(),
            'name' => 'Erika Musterfrau',
        ], $response);
    }

    public function testLoginReusesExistingTokenWithoutWriting(): void
    {
        $this->authenticate($this->user('existing-token'));
        $this->expectNoWrites();

        $this->assertJsonResponse([
            'token' => 'existing-token',
            'name' => 'Erika Musterfrau',
        ], $this->controller->login());
    }

    public function testLoginRejectsMissingAuthenticatedUser(): void
    {
        // TokenStorage ist vorhanden, enthaelt aber kein Security-Token.
        $this->expectNoWrites();
        $this->entityManager->expects(self::never())->method('getRepository');

        $this->assertJsonResponse([
            'error' => true, 'reason' => 'No user loged in',
        ], $this->controller->login());
    }

    public function testLogoutRemovesExistingToken(): void
    {
        $user = $this->user('api-token');
        $this->expectUserLookup($user);
        // Logout muss den Headerbenutzer verwenden, nicht den Sessionbenutzer.
        $sessionUser = $this->user('other-token');
        $this->authenticate($sessionUser);
        $this->entityManager->expects(self::once())->method('persist')
            ->with(self::identicalTo($user));
        $this->entityManager->expects(self::once())->method('flush');

        $response = $this->controller->logout($this->logoutRequest());

        self::assertNull($user->getOutlookPluginToken());
        self::assertSame('other-token', $sessionUser->getOutlookPluginToken());
        $this->assertJsonResponse(['token' => null], $response);
    }

    public function testLogoutRejectsUnknownTokenWithoutWriting(): void
    {
        $this->expectUserLookup(null);
        $this->expectNoWrites();

        $this->assertJsonResponse([
            'error' => true, 'reason' => 'No user loged found',
        ], $this->controller->logout($this->logoutRequest()));
    }

    public function testServerReturnsUsersServers(): void
    {
        $user = $this->user('api-token');
        $this->expectUserLookup($user);
        $first = $this->server(10, 'Server A');
        $second = $this->server(20, 'Server B');
        $this->serverManagement->expects(self::once())->method('getServersFromUser')
            ->with(self::identicalTo($user))->willReturn([$first, $second]);
        $this->expectNoWrites();

        $request = Request::create('/room/outlook/plugin/server');
        $request->headers->set('Authorization', 'Bearer api-token');

        $this->assertJsonResponse([
            ['id' => 10, 'name' => 'Server A'],
            ['id' => 20, 'name' => 'Server B'],
        ], $this->controller->server($request));
    }

    public function testServerReturnsEmptyListForUserWithoutServers(): void
    {
        $user = $this->user('api-token');
        $this->expectUserLookup($user);
        $this->serverManagement->expects(self::once())->method('getServersFromUser')
            ->with(self::identicalTo($user))->willReturn([]);
        $this->expectNoWrites();
        $request = Request::create('/room/outlook/plugin/server');
        $request->headers->set('Authorization', 'Bearer api-token');

        $this->assertJsonResponse([], $this->controller->server($request));
    }

    public function testServerRejectsUnknownToken(): void
    {
        $this->expectUserLookup(null);
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoWrites();
        $request = Request::create('/room/outlook/plugin/server');
        $request->headers->set('Authorization', 'Bearer api-token');

        $this->assertJsonResponse([
            'error' => true, 'reason' => 'User not found',
        ], $this->controller->server($request));
    }

    public function testCreateConferenceRejectsUnknownServer(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')->with('10')->willReturn(null);
        $this->entityManager->expects(self::once())->method('getRepository')
            ->with(Server::class)->willReturn($repository);
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoConferenceCreation();

        $this->assertJsonResponse([
            'error' => true, 'reason' => 'Server not found',
        ], $this->controller->createConference($this->conferenceRequest()));
    }

    public function testCreateConferenceRejectsUnknownUser(): void
    {
        $this->expectConferenceLookups($this->server(10, 'Server A'), null);
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoConferenceCreation();

        $this->assertJsonResponse([
            'error' => true, 'reason' => 'User not found',
        ], $this->controller->createConference($this->conferenceRequest()));
    }

    public function testCreateConferenceRejectsServerNotAllowedForUser(): void
    {
        $user = $this->user('api-token');
        $this->expectConferenceLookups($this->server(10, 'Server A'), $user);
        $this->serverManagement->expects(self::once())->method('getServersFromUser')
            ->with(self::identicalTo($user))->willReturn([$this->server(20, 'Other server')]);
        $this->expectNoConferenceCreation();

        $this->assertJsonResponse([
            'error' => true, 'reason' => 'Server not allowed for user',
        ], $this->controller->createConference($this->conferenceRequest()));
    }

    public function testCreateConferenceConfiguresPersistsAndReturnsRoom(): void
    {
        $user = $this->user('api-token');
        // Der Originalcontroller verwendet getUser() als Moderator.
        $this->authenticate($user);
        $server = $this->server(10, 'Server A');
        $this->expectConferenceLookups($server, $user);
        $this->serverManagement->expects(self::once())->method('getServersFromUser')
            ->with(self::identicalTo($user))->willReturn([$server]);

        $room = $this->createRoomMock();
        $room->method('getUid')->willReturn('room-uid-42');
        $room->method('getName')->willReturn('Testkonferenz');
        $room->expects(self::once())->method('setModerator')
            ->with(self::identicalTo($user))->willReturnSelf();
        foreach (['setPublic', 'setTotalOpenRooms', 'setIsFastConference'] as $setter) {
            $room->expects(self::once())->method($setter)->with(true)->willReturnSelf();
        }
        $this->conferenceService->expects(self::once())->method('createNewRoomFromName')
            ->with(self::callback(static fn ($name): bool => is_string($name) && $name !== ''),
                self::identicalTo($server))
            ->willReturn($room);
        $this->entityManager->expects(self::once())->method('persist')
            ->with(self::identicalTo($room));
        $this->entityManager->expects(self::once())->method('flush');

        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects(self::once())->method('generate')
            ->with('own_room_startPage', ['uid' => 'room-uid-42'], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/example-room/room-uid-42');
        $this->container->set('router', $router);
        $this->httpsUrl->expects(self::once())->method('createHttpsUrl')
            ->with('/example-room/room-uid-42')->willReturn('https://example.test/example-room/room-uid-42');

        $this->assertJsonResponse([
            ['url' => 'https://example.test/example-room/room-uid-42', 'title' => 'Testkonferenz'],
        ], $this->controller->createConference($this->conferenceRequest()));
    }

    public function testRouteAttributesDeclareExpectedPathsAndHttpMethods(): void
    {
        // Ein direkter Methodenaufruf testet keine HTTP-Methodenbeschraenkung.
        // Hier wird ausschliesslich die deklarierte Routing-Konfiguration geprueft.
        $reflection = new \ReflectionClass(OutlookPluginController::class);
        $prefixAttributes = $reflection->getAttributes(Route::class);
        self::assertCount(1, $prefixAttributes);
        self::assertSame('/room/outlook/plugin', $prefixAttributes[0]->newInstance()->getPath());

        $expectedRoutes = [
            'login' => ['/outlooklogin', ['GET']],
            'logout' => ['/logout', ['GET']],
            'server' => ['/server', ['GET']],
            'createConference' => ['/createConference', ['POST']],
        ];

        foreach ($expectedRoutes as $method => [$path, $httpMethods]) {
            $attributes = $reflection->getMethod($method)->getAttributes(Route::class);
            self::assertCount(1, $attributes, $method);
            $route = $attributes[0]->newInstance();
            self::assertSame($path, $route->getPath(), $method);
            self::assertSame($httpMethods, $route->getMethods(), $method);
        }
    }

    private function user(?string $token): User
    {
        // Zustandsbehafteter Stub ohne Annahmen ueber den Entity-Konstruktor.
        $user = $this->createStub(User::class);
        $user->method('getUid')->willReturn('test-user');
        $user->method('getFirstName')->willReturn('Erika');
        $user->method('getLastName')->willReturn('Musterfrau');
        $user->method('getOutlookPluginToken')->willReturnCallback(
            static function () use (&$token): ?string { return $token; }
        );
        $user->method('setOutlookPluginToken')->willReturnCallback(
            static function (?string $value) use (&$token, $user): User {
                $token = $value;
                return $user;
            }
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

    private function server(int $id, string $name): Server
    {
        $server = $this->createStub(Server::class);
        $server->method('getId')->willReturn($id);
        $server->method('getServerName')->willReturn($name);
        return $server;
    }

    private function expectUserLookup(?User $user): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findOneBy')
            ->with(['outlookPluginToken' => 'api-token'])->willReturn($user);
        $this->entityManager->expects(self::once())->method('getRepository')
            ->with(User::class)->willReturn($repository);
    }

    private function expectConferenceLookups(Server $server, ?User $user): void
    {
        $servers = $this->createMock(EntityRepository::class);
        $servers->expects(self::once())->method('find')->with('10')->willReturn($server);
        $users = $this->createMock(EntityRepository::class);
        $users->expects(self::once())->method('findOneBy')
            ->with(['outlookPluginToken' => 'api-token'])->willReturn($user);
        $this->entityManager->expects(self::exactly(2))->method('getRepository')
            ->willReturnMap([[Server::class, $servers], [User::class, $users]]);
    }

    private function conferenceRequest(): Request
    {
        $request = Request::create('/room/outlook/plugin/createConference', 'POST', ['server_id' => '10']);
        $request->headers->set('Authorization', 'Bearer api-token');
        return $request;
    }

    private function logoutRequest(): Request
    {
        $request = Request::create('/room/outlook/plugin/logout', 'GET');
        $request->headers->set('Authorization', 'Bearer api-token');
        return $request;
    }

    private function createRoomMock(): MockObject
    {
        // Die Raumklasse ist im bereitgestellten Ausschnitt nicht benannt.
        // Alternativ direkt: return $this->createMock(DeineRaumklasse::class);
        $type = (new \ReflectionMethod(PublicConferenceService::class, 'createNewRoomFromName'))
            ->getReturnType();
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()
            || !class_exists($type->getName())) {
            self::fail('In createRoomMock() bitte die konkrete Raumklasse einsetzen; der Service hat keinen eindeutigen Klassen-Rueckgabetyp.');
        }
        return $this->createMock($type->getName());
    }

    private function expectNoWrites(): void
    {
        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('flush');
    }

    private function expectNoConferenceCreation(): void
    {
        $this->expectNoWrites();
        $this->conferenceService->expects(self::never())->method('createNewRoomFromName');
        $this->httpsUrl->expects(self::never())->method('createHttpsUrl');
    }

    private function assertJsonResponse(array $expected, Response $response): void
    {
        // Auch Fehlerantworten verwenden im Originalcontroller HTTP 200.
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame($expected, json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
