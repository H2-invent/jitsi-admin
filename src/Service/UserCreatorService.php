<?php

namespace App\Service;

use App\Entity\User;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserCreatorService
{
    private $em;
    private $indexer;
    private $parameterBag;
    private $themeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        IndexUserService       $indexUserService,
        ParameterBagInterface  $parameterBag,
        ThemeService           $themeService,
    ) {
        $this->em           = $entityManager;
        $this->indexer      = $indexUserService;
        $this->parameterBag = $parameterBag;
        $this->themeService = $themeService;
    }

    public function createUser($email, $userName, $firstName = null, $lastName = null, $dryrun = false): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $userName]);
        if (!$user) {
            $user = new User();
            $user->setCreatedAt(new \DateTime())
                ->setUsername($userName)
                ->setLastName($lastName)
                ->setFirstName($firstName)
                ->setEmail($email)
                ->setRegisterId(md5(uniqid('ksdjhfkhsdkjhjksd', true)))
                ->setPassword('123')
                ->setUid(md5(uniqid()));
            $user->setIndexer($this->indexer->indexUser($user));
            if (!$dryrun) {
                $this->em->persist($user);
                $this->em->flush();
            }
        }
        return $user;
    }

    public function doAllowUserCreation(): bool
    {
        $allowParam = $this->parameterBag->get('strict_allow_user_creation');
        $allowParam = filter_var($allowParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;

        $allowTheme = $this->themeService->getThemeProperty('addressbookAddUser');
        $allowTheme = $allowTheme === null || (filter_var($allowTheme, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);

        return $allowParam && $allowTheme;
    }
}
