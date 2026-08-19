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
    /**
     * @var array<int, array<int, Deputy>>
     */
    private array $deputyCache = [];

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

    public function deputyIsFromLDAP(User $manager, User $deputy): bool
    {
        if (!isset($this->deputyCache[$manager->getId()])) {
            $this->deputyCache[$manager->getId()] = $this->entityManager
                ->getRepository(Deputy::class)
                ->findForManager($manager);
        }

        $dep = $this->deputyCache[$manager->getId()][$deputy->getId()] ?? null;

        return $dep !== null && $dep->isIsFromLdap() === true;
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
