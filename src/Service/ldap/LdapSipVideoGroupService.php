<?php

namespace App\Service\ldap;

use App\dataType\LdapType;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Entry;

class LdapSipVideoGroupService
{
    private LdapType $ldapType;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository         $userRepository,
    )
    {
    }

    /**
     * @return LdapType
     */
    public function getLdapType(): LdapType
    {
        return $this->ldapType;
    }

    /**
     * @param LdapType $ldapType
     * @return void
     */
    public function setLdapType(LdapType $ldapType): void
    {
        $this->ldapType = $ldapType;
    }

    /**
     * @param LdapType $ldapType
     * @return array|Entry[]
     */
    public function fetchUserIsSipVideoUser(LdapType $ldapType)
    {
        $sipVideoGroup = $ldapType->retrieveSipVideoUser();
        return $sipVideoGroup;
    }

    /**
     * @param LdapType $ldapType
     * @param $dryrun
     * @return array
     */
    public function getMembersFromSip(LdapType $ldapType)
    {

        $members = $this->fetchUserIsSipVideoUser($ldapType);
        if (count($members) > 0) {
            $members = $members[0]->getAttribute('member');
            return $members;
        }
        return [];
    }

    /**
     * @param LdapType[] $ldapType
     * @param $dryrun
     */
    public function connectSipVideoMembersFromLdapTypes($ldapTypes, $dryrun=false)
    {
        foreach ($ldapTypes as $data){
            $members = $this->getMembersFromSip($data);
            $this->addSipAttributeToUser($members,dryrun: $dryrun);
        }

    }



    /**
     * @param array $ldapMembers
     * @return void
     * This Function searches the user by the ldap sip video group members and adds the is sipvideo attibut to the user
     */
    public function addSipAttributeToUser(array $ldapMembers, $dryrun = false)
    {
        foreach ($ldapMembers as $data) {
            $user = $this->userRepository->findUsersfromLdapdn($data);
            if ($user && !$user->isIsSipVideoUser()) {
                $user->setIsSipVideoUser(true);
                $this->entityManager->persist($user);
            }
        }
        if (!$dryrun) {
            $this->entityManager->flush();
        } else {
            $this->entityManager->clear();
        }


    }

    /**
     * @param LdapType[] $ldapTypes
     * @return int
     * This function removes the SIP Video Attribute from USers which are not i nthe SIP video group anymore.
     * This ist the merge Function which should bes used to start the removal workflow
     */
    public function removeVideoSipFromUsers($ldapTypes, $dryrun = false): int
    {
        $count = 0;
        $userDns = [];
        foreach ($ldapTypes as $ldapType) {
            $members = $this->getMembersFromSip($ldapType,$dryrun);
            $userDns = array_merge($userDns, $members);
        }

        $count = $this->removeVideoSipFromUsersDnArray(userDns: $userDns);
        return $count;
    }

    /**
     * @param array $userDns
     * @return int
     *This function selects if the user is in the sip video group anymore. The sip video group members are an array of the dn of the members in the sip video group
     */
    public function removeVideoSipFromUsersDnArray(array $userDns, $dryrun = false): int
    {
        $count = 0;
        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            if ($user->getLdapUserProperties() && $user->isIsSipVideoUser()) { // is a ldap user
                if (!in_array($user->getLdapUserProperties()->getLdapDn(), $userDns)) {
                    $user->setIsSipVideoUser(false);
                    $this->entityManager->persist($user);
                    $count++;
                }
            }
        }
        if (!$dryrun) {
            $this->entityManager->flush();
        } else {
            $this->entityManager->clear();
        }

        return $count;
    }
}