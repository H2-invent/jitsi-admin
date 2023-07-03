<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class InviteService
{
    private $em;
    private $translator;
    private $router;
    private $mailer;
    private $parameterBag;
    private $twig;
    public function __construct(Environment $environment, ParameterBagInterface $parameterBag, MailerService $mailerService, EntityManagerInterface $entityManager, TranslatorInterface $translator, UrlGeneratorInterface $urlGenerator)
    {
        $this->translator = $translator;
        $this->em = $entityManager;
        $this->router = $urlGenerator;
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
        $this->twig = $environment;
    }


    public function connectUserWithEmail(User $userfromregisterId, User $user)
    {
        if (!$user->getTeam()) {
            $user->setTeam($userfromregisterId->getTeam());
        }
        if (!$user->getAkademieUser()) {
            $user->setAkademieUser($userfromregisterId->getAkademieUser());
        }
        foreach ($user->getTeamDsb() as $data) {
            $user->addTeamDsb($data);
        }
        $this->em->persist($user);
        $this->em->remove($userfromregisterId);
        $this->em->flush();
        return $user;
    }
}
