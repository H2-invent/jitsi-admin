<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Deputy;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeputyTwig extends AbstractExtension
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface  $parameterBag
    )
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('deputyIsFromLDAP', [$this, 'deputyIsFromLDAP']),
            new TwigFunction('userIsDisallowedToMakeDeputy', [$this, 'userIsDisallowedToMakeDeputy']),
        ];
    }

    public function deputyIsFromLDAP(User $manager, User $deputy)
    {
        $dep = $this->entityManager->getRepository(Deputy::class)->findOneBy(['manager' => $manager, 'deputy' => $deputy]);
        if ($dep && $dep->isIsFromLdap()) {
            return true;
        }
        return false;
    }

    public function userIsDisallowedToMakeDeputy(User $user): bool
    {
        if (!$user->getLdapUserProperties()) {
            return false;
        }

        if (in_array($user->getLdapUserProperties()->getLdapNumber(), json_decode($this->parameterBag->get('LDAP_DISALLOW_PROMOTE_DEPUTY')))){
           return  true;
        }
        return  false;
    }

}
