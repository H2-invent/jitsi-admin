<?php


namespace App\Service\ldap;


use App\dataType\LdapType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Exception\NotBoundException;
use Symfony\Component\Ldap\Ldap;

class LdapService
{

    private $ldapUserService;
    private $em;

    public function __construct(LdapUserService $ldapUserService, EntityManagerInterface $entityManager)
    {
        $this->ldapUserService = $ldapUserService;
        $this->em = $entityManager;

    }

    /**
     * This function creates a ldap connection
     * @param $url
     * @param $login
     * @param $password
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Ldap|null
     */
    public function createLDAP($url, $login, $password, $anonym = false): ?Ldap
    {
        try {
            $tmp = Ldap::create('ext_ldap', ['connection_string' => $url]);
            if ($anonym === false) {
                $tmp->bind($login, $password);
            } else {
                $tmp->bind();
            }
            return $tmp;
        } catch (\Exception $e) {
            throw $e;
        }

    }


    /**
     * this function queries for users in the ldap
     * @param Ldap $ldap
     * @param string $userDn
     * @param string $objectclass
     * @param string $scope
     * @return \Symfony\Component\Ldap\Entry[]
     */
    public function retrieveUser(LdapType $ldap)
    {

        $options = array(
            'scope' => $ldap->getScope(),
        );

        $query = $ldap->getLdap()->query($ldap->getUserDn(), $ldap->buildObjectClass(), $options);
        $user = $query->execute();
        return $user->toArray();
    }


    /**
     * @param LdapType $ldap
     * @return array
     * @throws \Exception
     */
    public function fetchLdap(LdapType $ldap, $dryRun = false)
    {

        $user = null;

        try {
            $userLdap =//Here we fetch all coresponding users from the LDAP
                $this->retrieveUser($ldap);
            foreach ($userLdap as $u) {// Here we itterate over the user from user
                $user[] = $this->ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($u, $ldap, $dryRun);
            }
        } catch (\Exception $e) {
            throw $e;
        }
        if (!$dryRun) {
            $this->ldapUserService->syncDeletedUser($ldap);
        }

        return array('ldap' => $ldap, 'user' => $user);
    }
}