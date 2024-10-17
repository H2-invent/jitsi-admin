<?php

namespace App\Security;

use App\Entity\FosUser;
use App\Entity\MyUser;
use App\Entity\User;
use App\Service\CreateHttpsUrl;
use App\Service\IndexUserService;
use App\Service\ThemeService;
use App\Service\UserCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class KeycloakAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private $clientRegistry;
    private $em;
    private $router;
    private $tokenStorage;
    private $userManager;
    private $paramterBag;
    private $userCreatorService;
    private $indexer;
    private $logger;
    private ThemeService $themeService;

    public function __construct(
        ThemeService                  $themeService,
        LoggerInterface               $logger,
        IndexUserService              $indexUserService,
        UserCreatorService            $userCreatorService,
        ParameterBagInterface         $parameterBag,
        TokenStorageInterface         $tokenStorage,
        ClientRegistry                $clientRegistry,
        EntityManagerInterface        $em,
        RouterInterface               $router,
        private CreateHttpsUrl        $createHttpsUrl,
        private UrlGeneratorInterface $urlGenerator,
    )
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->paramterBag = $parameterBag;
        $this->userCreatorService = $userCreatorService;
        $this->indexer = $indexUserService;
        $this->logger = $logger;
        $this->themeService = $themeService;
    }

    public function supports(Request $request): bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_keycloak_check';
    }

    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getauth0Client());
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('keycloak_main');
        $accessToken = $this->fetchAccessToken($client, [
            'redirect_uri' => $this->createHttpsUrl->replaceSchemeOfAbsolutUrl($this->urlGenerator->generate('connect_keycloak_check', [], UrlGenerator::ABSOLUTE_URL))
        ]);
        $request->getSession()->set('id_token', $accessToken->getValues()['id_token']);
        $passport = new SelfValidatingPassport(
            new UserBadge(
                $accessToken->getToken(),
                function () use ($accessToken, $client) {
                    /** @var KeycloakUser $keycloakUser */
                    $keycloakUser = $client->fetchUserFromToken($accessToken);
                    try {
                        //When the keycloak USer delivers a
                        $email = $keycloakUser->getEmail();
                    } catch (\Exception $e) {
                        try {
                            $email = $keycloakUser->toArray()['preferred_username'];
                        } catch (\Exception $e) {
                        }
                    }
                    $id = $keycloakUser->getId();
                    $this->logger->debug($id);
                    $firstName = $keycloakUser->toArray()['given_name'];
                    $this->logger->debug($firstName);
                    $lastName = $keycloakUser->toArray()['family_name'];
                    $this->logger->debug($lastName);
                    $username = isset($keycloakUser->toArray()['preferred_username']) ? $keycloakUser->toArray()['preferred_username'] : null;
                    $this->logger->debug($username);
                    $groups = null;
                    if (isset($keycloakUser->toArray()['groups'])) {
                        $groups = $keycloakUser->toArray()['groups'];
                    }


                    // 1) have they logged in with keycloak before then login the user
                    $existingUser = $this->em->getRepository(User::class)->findOneBy(['keycloakId' => $id]);
                    if ($existingUser) {
                        if (!$username) {
                            $username = $email;
                        }
                        $existingUser->setLastLogin(new \DateTime());
                        $existingUser->setEmail($email);
                        $existingUser->setFirstName($firstName);
                        $existingUser->setLastName($lastName);
                        $existingUser->setUsername($username);
                        $existingUser->setGroups($groups);
                        $existingUser->setIndexer($this->indexer->indexUser($existingUser));
                        $this->em->persist($existingUser);
                        $this->em->flush();
                        return $existingUser;
                    }

                    // 2) it is an USer which was invited via the invitiation email or the user is a synced user from the LDAP. This USer tries now to get an access via keycloak
                    $existingUser = null;
                    $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
                    if (!$existingUser && $username !== null) {
                        $existingUser = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
                    }
                    if ($existingUser) {
                        if (!$username) {
                            $username = $email;
                        }
                        $existingUser->setKeycloakId($id);
                        $existingUser->setLastLogin(new \DateTime());
                        $existingUser->setEmail($email);
                        $existingUser->setFirstName($firstName);
                        $existingUser->setLastName($lastName);
                        $existingUser->setUsername($username);
                        $existingUser->setGroups($groups);
                        $this->em->persist($existingUser);
                        $existingUser->setIndexer($this->indexer->indexUser($existingUser));
                        $this->em->flush();
                        return $existingUser;
                    }

                    // the user never logged in with this email adress neither keycloak
                    if ($this->paramterBag->get('strict_allow_user_creation') == 1) {
                        // if the creation of a user is allowed from the security policies
                        if (!$username) {
                            $username = $email;
                        }
                        $newUser = $this->userCreatorService->createUser($email, $username, $firstName, $lastName);
                        $newUser
                            ->setLastLogin(new \DateTime())
                            ->setKeycloakId($id)
                            ->setGroups($groups);
                        $newUser->setIndexer($this->indexer->indexUser($newUser));
                        $this->em->persist($newUser);
                        $this->em->flush();
                        return $newUser;
                    }
                    return null;
                }
            )
        );
        $passport->setAttribute('id_token', 'null');
        $passport->setAttribute('scope', 'openid');

        return $passport;
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {

        // change "app_homepage" to some route in your app
        $targetUrl = $this->getTargetPath($request->getSession(), 'main');
        if (!$targetUrl) {
            $targetUrl = $this->router->generate('dashboard');
        }

        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->error($exception->getMessage());
        return new RedirectResponse($this->router->generate('index'));
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        $targetUrl = $this->router->generate('login_keycloak');
        return new RedirectResponse($targetUrl);
    }
}
