<?php

namespace App\Helper;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class JitsiAdminController extends AbstractController
{
    protected ManagerRegistry $doctrine;
    protected TranslatorInterface $translator;
    protected LoggerInterface $logger;
    protected ParameterBagInterface $parameterBag;

    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        $this->doctrine = $managerRegistry;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?UserInterface
    {
        $user = parent::getUser();
        return $user instanceof User ? $user : null;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getDoctrine(): ManagerRegistry
    {
        return $this->doctrine;
    }

    protected function getSessionUser(SessionInterface $session): ?User
    {

        $user = $this->getUser();

        if (!$user) {
            $user = $this->doctrine->getRepository(User::class)->find($session->get('userId'));
        }

        return $user;
    }
}
