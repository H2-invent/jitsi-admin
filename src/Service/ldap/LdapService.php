<?php


namespace App\Service\ldap;



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
    public function createLDAP($url, $login, $password, InputInterface $input, OutputInterface $output): ?Ldap
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $tmp = Ldap::create('ext_ldap', ['connection_string' => $url]);
            $tmp->bind($login, $password);
            $io->success('We connect successfully to: ' . $url);
            return $tmp;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            throw new InvalidCredentialsException();
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
    public function retrieveUser(Ldap $ldap, string $userDn, string $objectclass, string $scope)
    {

        $options = array(
            'scope' => $scope
        );
        $query = $ldap->query($userDn, $objectclass, $options);
        $user = $query->execute();

        return $user->toArray();
    }

    /**
     * @param $objectClassString
     * @return string
     */
    public function buildObjectClass($objectClassString): string
    {
        $objectclass = '(&(|';
        foreach (explode(',', $objectClassString) as $data2) {
            $objectclass .= '(objectclass=' . $data2 . ')';
        }
        $objectclass .= '))';
        return $objectclass;
    }


    /**
     * @param Ldap $ldap
     * @param $userDn
     * @param $objectClasses
     * @param $scope
     * @param $mapper
     * @param $url
     * @param $usernameAttribute
     * @param OutputInterface $output
     * @param InputInterface $input
     * @return Entry[]|null
     */
    public function fetchLdap(Ldap $ldap, $userDn, $objectClasses, $scope, $mapper, $url, $usernameAttribute, OutputInterface $output, InputInterface $input){
        $io = new SymfonyStyle($input, $output);
        try {
            $user =
                $this->retrieveUser(
                    $ldap,
                    $userDn,
                    $this->buildObjectClass($objectClasses),
                    $scope
                );

            $table = new Table($output);
            foreach ($user as $u) {
                $us = $this->ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($u, $usernameAttribute,$mapper,$url);
                $table->addRow([implode(',', $u->getAttribute('mail')), implode(',', $u->getAttribute('uid')), $u->getDn()]);
            }

            $table->setHeaders(['email', 'uid', 'dn']);
            $table->setHeaderTitle($url);
            $table->setStyle('borderless');
            $table->render();

        } catch (LdapException $e) {
            $io->error('Fehler in LDAP: ' . $url);
            $io->error('Fehler: ' . $e->getMessage());
            return null;
        } catch (NotBoundException $e) {
            $io->error('Fehler in LDAP: ' . $url);
            $io->error('Fehler: ' . $e->getMessage());
            return null;
        }
        $this->ldapUserService->syncDeletedUser($ldap,$url);
        return $user;
    }
}