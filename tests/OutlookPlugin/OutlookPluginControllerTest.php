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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;


/**
 * Unit-Tests ohne Symfony-Kernel oder Datenbank.
 * Die Entity- und Service-Klassen sowie ihre verwendeten Methoden muessen mockbar sein.
 * Die Raum- und Caller-Mocks werden aus den deklarierten Rueckgabetypen abgeleitet.
 * Route-Assertions pruefen Attribute, nicht die Router-/Firewall-Konfiguration.
 */
final class OutlookPluginControllerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ServerUserManagment&MockObject $serverManagement;
    private PublicConferenceService&MockObject $conferenceService;
    private CreateHttpsUrl&MockObject $httpsUrl;
    private LoggerInterface&MockObject $logger;
    private OutlookPluginController $controller;
    private Container $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->serverManagement = $this->createMock(ServerUserManagment::class);
        $this->conferenceService = $this->createMock(PublicConferenceService::class);
        $this->httpsUrl = $this->createMock(CreateHttpsUrl::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Der finale Controller wird real instanziiert, nicht gemockt.
        $this->controller = new OutlookPluginController(
            $this->entityManager,
            $this->serverManagement,
            $this->conferenceService,
            $this->httpsUrl,
            $this->logger,
        );
        $this->container = new Container();
        $this->container->set('security.token_storage', new TokenStorage());
        $this->controller->setContainer($this->container);
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
        $this->assertJsonResponse(['success' => true], $response);
    }

    public function testLogoutReturnsErrorWhenReadingAuthorizationThrows(): void
    {
        $request = $this->withFailingAuthorizationRead($this->logoutRequest());

        $this->entityManager->expects(self::never())->method('getRepository');
        $this->expectNoWrites();

        $this->assertJsonResponse(
            ['error' => 'Error reading token'],
            $this->controller->logout($request),
        );
    }

    public function testLogoutRejectsEmptyTokenWithoutRepositoryAccess(): void
    {
        $request = $this->logoutRequest();
        $request->headers->set('Authorization', 'Bearer ');

        $this->entityManager->expects(self::never())->method('getRepository');
        $this->expectNoWrites();

        $this->assertJsonResponse(
            ['error' => 'Error reading token'],
            $this->controller->logout($request),
        );
    }

    public function testLogoutDoesNotWriteWhenFoundUserHasNoToken(): void
    {
        // Deckt den defensiven Zweig nach der Repository-Suche ab.
        $user = $this->user(null);
        $this->expectUserLookup($user);
        $this->expectNoWrites();

        $response = $this->controller->logout($this->logoutRequest());

        self::assertNull($user->getOutlookPluginToken());
        $this->assertJsonResponse(['success' => true], $response);
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

        $request = Request::create('/api/v1/outlook/plugin/server');
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
        $request = Request::create('/api/v1/outlook/plugin/server');
        $request->headers->set('Authorization', 'Bearer api-token');

        $this->assertJsonResponse([], $this->controller->server($request));
    }

    public function testServerReturnsErrorWhenReadingAuthorizationThrows(): void
    {
        $request = $this->withFailingAuthorizationRead(
            Request::create('/api/v1/outlook/plugin/server'),
        );

        $this->entityManager->expects(self::never())->method('getRepository');
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoWrites();

        $this->assertJsonResponse(
            ['error' => 'Error reading token'],
            $this->controller->server($request),
        );
    }

    public function testServerRejectsEmptyTokenWithoutRepositoryAccess(): void
    {
        $request = Request::create('/api/v1/outlook/plugin/server');
        $request->headers->set('Authorization', 'Bearer ');

        $this->entityManager->expects(self::never())->method('getRepository');
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoWrites();

        $this->assertJsonResponse(
            ['error' => 'Error reading token'],
            $this->controller->server($request),
        );
    }

    public function testServerRejectsUnknownToken(): void
    {
        $this->expectUserLookup(null);
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoWrites();
        $request = Request::create('/api/v1/outlook/plugin/server');
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

    public function testCreateConferenceReturnsErrorWhenReadingAuthorizationThrows(): void
    {
        $server = $this->server(10, 'Server A');
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')
            ->with('10')->willReturn($server);

        // Der Server muss existieren, damit der Controller den try-Block erreicht.
        $this->entityManager->expects(self::once())->method('getRepository')
            ->with(Server::class)->willReturn($repository);
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoConferenceCreation();

        $request = $this->withFailingAuthorizationRead($this->conferenceRequest());

        $this->assertJsonResponse(
            ['error' => 'Error reading token'],
            $this->controller->createConference($request),
        );
    }

    public function testCreateConferenceRejectsEmptyTokenAfterServerLookup(): void
    {
        $server = $this->server(10, 'Server A');
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('find')
            ->with('10')->willReturn($server);

        // Der Controller sucht den Server, bevor er den Token prueft.
        $this->entityManager->expects(self::once())->method('getRepository')
            ->with(Server::class)->willReturn($repository);
        $this->serverManagement->expects(self::never())->method('getServersFromUser');
        $this->expectNoConferenceCreation();

        $request = $this->conferenceRequest();
        $request->headers->set('Authorization', 'Bearer ');

        $this->assertJsonResponse(
            ['error' => 'Error reading token'],
            $this->controller->createConference($request),
        );
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

    public function testCreateConferenceConfiguresPersistsAndReturnsRoomWithoutPhone(): void
    {
        $room = $this->expectCreatedConference(null);
        $room->expects(self::never())->method('getCallerRoom');
        $this->logger->expects(self::never())->method('error');

        $this->assertCreatedConferencePhone([]);
    }

    public function testCreateConferenceReturnsStructuredDialInNumbers(): void
    {
        $configuration = $this->dialInConfiguration(true);
        $room = $this->expectCreatedConference(
            json_encode($configuration, JSON_THROW_ON_ERROR)
        );
        $caller = $this->createCallerMock($room);
        $caller->expects(self::once())->method('getCallerId')->willReturn('12345678');
        $room->expects(self::once())->method('getCallerRoom')->willReturn($caller);
        $this->logger->expects(self::never())->method('error');

        // Vollstaendiges Konfigurationsobjekt, nicht nur die Laendernummern.
        $this->assertCreatedConferencePhone([
            'numbers' => $configuration,
            'dialIn' => '12345678',
        ]);
    }

    public function testCreateConferenceStillReturnsNumbersWhenNumbersEnabledIsFalse(): void
    {
        $configuration = $this->dialInConfiguration(false);
        $room = $this->expectCreatedConference(
            json_encode($configuration, JSON_THROW_ON_ERROR)
        );
        $caller = $this->createCallerMock($room);
        $caller->expects(self::once())->method('getCallerId')->willReturn('12345678');
        $room->expects(self::once())->method('getCallerRoom')->willReturn($caller);
        $this->logger->expects(self::never())->method('error');

        // Charakterisierung des Ist-Verhaltens: Der Controller prueft das Flag nicht.
        $this->assertCreatedConferencePhone([
            'numbers' => $configuration,
            'dialIn' => '12345678',
        ]);
    }

    public function testCreateConferenceReturnsNullNumbersForInvalidJsonWithoutLogging(): void
    {
        $room = $this->expectCreatedConference('{invalid-json');
        $caller = $this->createCallerMock($room);
        $caller->expects(self::once())->method('getCallerId')->willReturn('12345678');
        $room->expects(self::once())->method('getCallerRoom')->willReturn($caller);
        $this->logger->expects(self::never())->method('error');

        // json_decode() ohne JSON_THROW_ON_ERROR wirft hier keine Exception.
        $this->assertCreatedConferencePhone([
            'numbers' => null,
            'dialIn' => '12345678',
        ]);
    }

    public function testCreateConferenceLogsCallerExceptionAndKeepsNumbers(): void
    {
        $configuration = $this->dialInConfiguration(true);
        $room = $this->expectCreatedConference(
            json_encode($configuration, JSON_THROW_ON_ERROR)
        );
        $room->expects(self::once())->method('getCallerRoom')
            ->willThrowException(new \RuntimeException('Caller lookup failed'));
        $this->logger->expects(self::once())->method('error')
            ->with('Caller lookup failed');

        // numbers wurde vor der Exception bereits gesetzt; dialIn fehlt.
        $this->assertCreatedConferencePhone(['numbers' => $configuration]);
    }

    public function testApiRoutesCombineClassAndMethodAttributes(): void
    {
        // Attribute-Vertrag; keine funktionale Pruefung des Symfony-Routers.
        $reflection = new \ReflectionClass(OutlookPluginController::class);
        self::assertFalse($reflection->hasMethod('login'));
        $classAttributes = $reflection->getAttributes(Route::class);
        self::assertCount(1, $classAttributes);
        $prefix = $classAttributes[0]->newInstance();
        self::assertSame('/api/v1/outlook/plugin', $prefix->getPath());
        self::assertSame('app_outlook_plugin', $prefix->getName());

        $expectedRoutes = [
            'logout' => ['/logout', ['GET'], '/api/v1/outlook/plugin/logout', 'app_outlook_pluginlogout'],
            'server' => ['/server', ['GET'], '/api/v1/outlook/plugin/server', 'app_outlook_pluginserver'],
            'createConference' => ['/createConference', ['POST'], '/api/v1/outlook/plugin/createConference', 'app_outlook_plugincreateConference'],
        ];

        foreach ($expectedRoutes as $method => [$path, $httpMethods, $fullPath, $fullName]) {
            $attributes = $reflection->getMethod($method)->getAttributes(Route::class);
            self::assertCount(1, $attributes, $method);
            $route = $attributes[0]->newInstance();
            self::assertSame($path, $route->getPath(), $method);
            self::assertSame($method, $route->getName(), $method);
            self::assertSame($httpMethods, $route->getMethods(), $method);
            self::assertSame($fullPath, $prefix->getPath() . $route->getPath(), $method);
            self::assertSame($fullName, $prefix->getName() . $route->getName(), $method);
        }
    }

    private function user(?string $token): User
    {
        // Zustandsbehafteter Stub ohne Annahmen ueber den Entity-Konstruktor.
        $user = $this->createStub(User::class);
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

    private function withFailingAuthorizationRead(Request $request): Request
    {
        // Gezielte Exception fuer catch (\Exception), statt nur eines leeren Tokens.
        $headers = $this->createMock(HeaderBag::class);
        $headers->expects(self::once())
            ->method('get')
            ->with('Authorization')
            ->willThrowException(new \RuntimeException('Authorization read failed'));

        $request->headers = $headers;

        return $request;
    }

    private function conferenceRequest(): Request
    {
        $request = Request::create('/api/v1/outlook/plugin/createConference', 'POST', ['server_id' => '10']);
        $request->headers->set('Authorization', 'Bearer api-token');
        return $request;
    }

    private function logoutRequest(): Request
    {
        $request = Request::create('/api/v1/outlook/plugin/logout', 'GET');
        $request->headers->set('Authorization', 'Bearer api-token');
        return $request;
    }

    /** Konfiguriert alle gemeinsamen Erwartungen eines erfolgreichen Aufrufs. */
    private function expectCreatedConference(?string $numberConfiguration): MockObject
    {
        $user = $this->user('api-token');
        // Ein anderer Sessionbenutzer darf nicht zum Moderator werden.
        $this->authenticate($this->user('session-token'));
        $server = $this->server(10, 'Server A');
        $server->method('getJigasiNumberUrl')->willReturn($numberConfiguration);
        $this->expectConferenceLookups($server, $user);
        $this->serverManagement->expects(self::once())->method('getServersFromUser')
            ->with(self::identicalTo($user))->willReturn([$server]);

        $room = $this->createRoomMock();
        $room->method('getUid')->willReturn('room-uid-42');
        $room->method('getName')->willReturn('Testkonferenz');
        $room->method('getId')->willReturn(32);
        $room->expects(self::once())->method('setModerator')
            ->with(self::identicalTo($user))->willReturnSelf();
        foreach (['setPublic', 'setTotalOpenRooms', 'setIsFastConference'] as $setter) {
            $room->expects(self::once())->method($setter)->with(true)->willReturnSelf();
        }
        $this->conferenceService->expects(self::once())->method('createNewRoomFromName')
            ->with(
                self::callback(static fn ($name): bool =>
                    is_string($name) && preg_match('/^[a-f0-9]{32}$/', $name) === 1
                ),
                self::identicalTo($server)
            )
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
            ->with('/example-room/room-uid-42')
            ->willReturn('https://example.test/example-room/room-uid-42');

        return $room;
    }

    private function dialInConfiguration(bool $enabled): array
    {
        return [
            'message' => 'Einwahlnummern',
            'numbers' => [
                'DE' => '04122/012345',
                'CH' => '04122/012345',
            ],
            'numbersEnabled' => $enabled,
        ];
    }

    private function assertCreatedConferencePhone(array $phone): void
    {
        $response = $this->controller->createConference($this->conferenceRequest());
        $this->assertJsonResponse([
            'url' => 'https://example.test/example-room/room-uid-42',
            'title' => 'Testkonferenz',
            'id' => 32,
            'phone' => $phone,
        ], $response);
    }

    private function createRoomMock(): MockObject
    {
        // Alternativ: return $this->createMock(KonkreteRaumklasse::class);
        return $this->createMethodReturnMock(
            PublicConferenceService::class,
            'createNewRoomFromName'
        );
    }

    private function createCallerMock(MockObject $room): MockObject
    {
        // Alternativ: return $this->createMock(KonkreteCallerKlasse::class);
        return $this->createMethodReturnMock(get_class($room), 'getCallerRoom');
    }

    private function createMethodReturnMock(string $class, string $method): MockObject
    {
        $reflection = new \ReflectionMethod($class, $method);
        $type = $reflection->getReturnType();
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            self::fail(sprintf(
                '%s::%s() hat keinen eindeutigen Klassen-Rueckgabetyp. '
                . 'Bitte im zugehoerigen Mock-Helper die konkrete Entity-Klasse einsetzen.',
                $class,
                $method
            ));
        }
        $name = $type->getName();
        if ($name === 'self' || $name === 'static') {
            $name = $reflection->getDeclaringClass()->getName();
        } elseif ($name === 'parent') {
            $parent = $reflection->getDeclaringClass()->getParentClass();
            self::assertNotFalse($parent);
            $name = $parent->getName();
        }
        if (!class_exists($name) && !interface_exists($name)) {
            self::fail(sprintf('Der Rueckgabetyp %s kann nicht geladen werden.', $name));
        }
        return $this->createMock($name);
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
