<?php

namespace App\Service\ldap;

use App\dataType\LdapType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Entry;

class LdapSipVideoGroupService
{
    private LdapType $ldapType;

    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function getLdapType(): LdapType
    {
        return $this->ldapType;
    }

    public function setLdapType(LdapType $ldapType): void
    {
        $this->ldapType = $ldapType;
    }

    public function DetectUserIsSipVideoUser(LdapType $ldapType, $dryrun)
    {
        $sipVideoGroup = $ldapType->retrieveSipVideoUser();
        return $sipVideoGroup;
    }


}