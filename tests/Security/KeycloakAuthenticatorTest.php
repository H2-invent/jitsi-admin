<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\KeycloakAuthenticator;
use App\Service\CreateHttpsUrl;
use App\Service\IndexUserService;
use App\Service\UserCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

#[CoversClass(KeycloakAuthenticator::class)]
class KeycloakAuthenticatorTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testSupportsReturnsTrueOnlyForTheKeycloakCheckRoute(): void
    {
        $authenticator = $this->authenticator();
        $request = new Request();

        $request->attributes->set('_route', 'connect_keycloak_check');
        self::assertTrue($authenticator->supports($request));

        $request->attributes->set('_route', 'index');
        self::assertFalse($authenticator->supports($request));

        $request->attributes->set('_route', null);
        self::assertFalse($authenticator->supports($request));
    }

    public function testStartRedirectsToKeycloakLogin(): void
    {
        $response = $this->authenticator()->start(new Request(), null);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($this->router()->generate('login_keycloak'), $response->getTargetUrl());
    }

    public function testOnAuthenticationFailureRedirectsToIndex(): void
    {
        $response = $this->authenticator()->onAuthenticationFailure(new Request(), new AuthenticationException('invalid credentials'));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($this->router()->generate('index'), $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessUsesTargetPathFromSession(): void
    {
        $request = $this->requestWithSession();
        $request->getSession()->set('_security.main.target_path', '/room/4711');

        $response = $this->authenticator()->onAuthenticationSuccess($request, new NullToken(), 'main');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/room/4711', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessFallsBackToDashboard(): void
    {
        $response = $this->authenticator()->onAuthenticationSuccess($this->requestWithSession(), new NullToken(), 'main');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($this->router()->generate('dashboard'), $response->getTargetUrl());
    }

    public function testGetCredentialsCallsUndefinedGetAuth0ClientMethod(): void
    {
        // Production issue: getCredentials() calls $this->getauth0Client(), which does not exist.
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('getauth0Client');

        $this->authenticator()->getCredentials(new Request());
    }

    public function testAuthenticateBuildsPassportAndUpdatesExistingKeycloakUser(): void
    {
        $em = $this->entityManager();
        $user = $this->persistUser($em, 'kc-existing@local.de', 'kc-existing', 'kc-sub-existing');
        $userId = $user->getId();

        $accessToken = new AccessToken(['access_token' => 'token-abc', 'id_token' => 'id-token-xyz']);
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('getAccessToken')->willReturn($accessToken);
        $client->method('fetchUserFromToken')->willReturn(new KeycloakResourceOwner([
            'sub' => 'kc-sub-existing',
            'email' => 'kc-new@local.de',
            'given_name' => 'NewFirst',
            'family_name' => 'NewLast',
            'preferred_username' => 'kc-new-user',
        ]));

        $request = $this->requestWithSession();
        $passport = $this->authenticator($client)->authenticate($request);

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);
        self::assertSame('id-token-xyz', $request->getSession()->get('id_token'));
        self::assertSame('null', $passport->getAttribute('id_token'));
        self::assertSame('openid', $passport->getAttribute('scope'));

        $badge = $passport->getBadge(UserBadge::class);
        self::assertInstanceOf(UserBadge::class, $badge);
        self::assertSame('token-abc', $badge->getUserIdentifier());

        $resolvedUser = $passport->getUser();
        self::assertInstanceOf(User::class, $resolvedUser);
        self::assertSame($userId, $resolvedUser->getId());
        self::assertSame('kc-new@local.de', $resolvedUser->getEmail());
        self::assertSame('NewFirst', $resolvedUser->getFirstName());
        self::assertSame('NewLast', $resolvedUser->getLastName());
        self::assertSame('kc-new-user', $resolvedUser->getUsername());
        self::assertNotNull($resolvedUser->getLastLogin());
        self::assertSame('kc-new-user kc-new@local.de newfirst newlast', $resolvedUser->getIndexer());

        $em->clear();
        self::assertSame('kc-new-user', $em->getRepository(User::class)->find($userId)->getUsername());
    }

    public function testAuthenticateReusesInvitedUserByEmailAndStoresKeycloakId(): void
    {
        $em = $this->entityManager();
        $user = $this->persistUser($em, 'kc-invited@local.de', 'kc-invited', null);
        $userId = $user->getId();

        $accessToken = new AccessToken(['access_token' => 'token-def', 'id_token' => 'id-token-123']);
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('getAccessToken')->willReturn($accessToken);
        $client->method('fetchUserFromToken')->willReturn(new KeycloakResourceOwner([
            'sub' => 'kc-sub-invited',
            'email' => 'kc-invited@local.de',
            'given_name' => 'Invited',
            'family_name' => 'Person',
            'preferred_username' => 'kc-invited',
        ]));

        $passport = $this->authenticator($client)->authenticate($this->requestWithSession());
        $resolvedUser = $passport->getUser();

        self::assertInstanceOf(User::class, $resolvedUser);
        self::assertSame($userId, $resolvedUser->getId());
        self::assertSame('kc-sub-invited', $resolvedUser->getKeycloakId());
        self::assertSame('kc-invited@local.de', $resolvedUser->getEmail());
        self::assertSame('Invited', $resolvedUser->getFirstName());
        self::assertSame('Person', $resolvedUser->getLastName());

        $em->clear();
        self::assertSame('kc-sub-invited', $em->getRepository(User::class)->find($userId)->getKeycloakId());
    }

    private function authenticator(?OAuth2ClientInterface $client = null): KeycloakAuthenticator
    {
        $registry = $this->createMock(ClientRegistry::class);
        $registry->method('getClient')->with('keycloak_main')->willReturn($client);

        $container = self::getContainer();

        return new KeycloakAuthenticator(
            $container->get(LoggerInterface::class),
            $container->get(IndexUserService::class),
            $container->get(UserCreatorService::class),
            $container->get(ParameterBagInterface::class),
            $container->get(TokenStorageInterface::class),
            $registry,
            $container->get(EntityManagerInterface::class),
            $container->get(RouterInterface::class),
            $container->get(CreateHttpsUrl::class),
            $container->get(UrlGeneratorInterface::class),
        );
    }

    private function requestWithSession(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }

    private function persistUser(EntityManagerInterface $em, string $email, string $username, ?string $keycloakId): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setUsername($username)
            ->setFirstName('OldFirst')
            ->setLastName('OldLast')
            ->setCreatedAt(new \DateTime())
            ->setKeycloakId($keycloakId);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    private function router(): RouterInterface
    {
        return self::getContainer()->get(RouterInterface::class);
    }
}
