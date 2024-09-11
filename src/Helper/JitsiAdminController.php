<?php

namespace App\Helper;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

class JitsiAdminController extends AbstractController
{
    protected $doctrine;
    protected $translator;
    protected $logger;
    protected $parameterBag;

    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        $this->doctrine = $managerRegistry;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return ManagerRegistry
     */
    public function getDoctrine(): ManagerRegistry
    {
        return $this->doctrine;
    }

    protected function getSessionUser(Session $session)
    {

        $user = $this->getUser();

        if (!$user) {
            $user = $this->doctrine->getRepository(User::class)->find($session->get('userId'));
        }

        return $user;
    }
}
