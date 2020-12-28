<?php


namespace App\Security;


use App\Entity\FosUser;
use App\Entity\MyUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GuardServiceKeycloak extends SocialAuthenticator
{
    use TargetPathTrait;
    private $clientRegistry;
    private $em;
    private $router;
    private $tokenStorage;
    private $userManager;

    public function __construct(TokenStorageInterface $tokenStorage, ClientRegistry $clientRegistry, EntityManagerInterface $em, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    public function supports(Request $request)
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_keycloak_check';
    }

    public function getCredentials(Request $request)
    {

        return $this->fetchAccessToken($this->getauth0Client());
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {

        /** @var KeycloakUser $keycloakUser */
        $keycloakUser = $this->getauth0Client()->fetchUserFromToken($credentials);
        $email = $keycloakUser->getEmail();
        $id = $keycloakUser->getId();
        $firstName = $keycloakUser->toArray()['given_name'];
        $lastName = $keycloakUser->toArray()['family_name'];
        // 1) have they logged in with keycloak befor then login the user
        $existingUser = $this->em->getRepository(User::class)->findOneBy(array('keycloakId' => $id));
        if ($existingUser) {
            $existingUser->setLastLogin(new \DateTime());
            $existingUser->setEmail($email);
            $existingUser->setFirstName($firstName);
            $existingUser->setLastName($lastName);
            $existingUser->setUsername($email);
            $this->em->persist($existingUser);
            $this->em->flush();
            return $existingUser;
        }

        // 1) it is an old USer from FOS USer time never loged in from keycloak
        $existingUser = null;
        $existingUser = $this->em->getRepository(User::class)->findOneBy(array('email' => $email));
        if ($existingUser) {
            $existingUser->setKeycloakId($id);
            $existingUser->setLastLogin(new \DateTime());
            $existingUser->setEmail($email);
            $existingUser->setFirstName($firstName);
            $existingUser->setLastName($lastName);
            $existingUser->setUsername($email);
            $this->em->persist($existingUser);
            $this->em->flush();
            return $existingUser;
        }

        // the user never logged in with this email adress or keycloak
        $newUser = new User();
        $newUser->setPassword('123')
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setUuid($email)
            ->setEmail($email)
            ->setCreatedAt(new \DateTime())
            ->setLastLogin(new \DateTime())
            ->setKeycloakId($id)
            ->setUsername($email);
        $this->em->persist($newUser);
        $this->em->flush();
        return $newUser;

    }

    /**
     * @return KeycloakClient
     */
    private function getauth0Client()
    {
        return $this->clientRegistry
            ->getClient('keycloak_main');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {

        // change "app_homepage" to some route in your app
        $targetUrl = $this->getTargetPath($request->getSession(), 'main');
        if (!$targetUrl) {
            $targetUrl = $this->router->generate('dashboard');
        }

        return new RedirectResponse($targetUrl);

    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new RedirectResponse($this->router->generate('no_team'));
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $targetUrl = $this->router->generate('login_keycloak');
        return new RedirectResponse($targetUrl);
    }

}



