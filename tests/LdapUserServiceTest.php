<?php

namespace App\Tests;

use App\dataType\LdapType;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function GuzzleHttp\Promise\all;

class LdapUserServiceTest extends KernelTestCase
{
    public function testRetrieveUserfromDatabasefromUserNameAttribute(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389', 'uid=admin,ou=system', 'password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl('ldap://localhost:10389');
        $ldapType->setSerVerId('Server1');
        $ldapType->setPassword('password');
        $ldapType->setScope('sub');
        $ldapType->setMapper(array("firstName" => "givenName", "lastName" => "sn", "email" => "uid"));
        $ldapType->setSpecialFields(array("ou" => "ou", "departmentNumber" => "departmentNumber"));
        $ldapType->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapType->setBindType('none');
        $ldapType->setRdn('uid');
        $ldapType->setLdap($ldap);
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');
        $entry = $ldapConnection->retrieveUser($ldap,'o=unitTest,dc=example,dc=com','person,organizationalPerson,user','sub');
        $users = array();
        foreach ($entry as $data){
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data,$ldapType);
        }
        $this->assertEquals(3,sizeof($users));
        $allUSers = $ldapUserService->connectUserwithAllUSersInAdressbock();
        foreach ($allUSers as $data){

            $this->assertEquals(sizeof($allUSers),sizeof($data->getAddressbook()));
        }


        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(array('username'=>'unitTest1'));
        $this->assertNotEquals(null,$ldapUserService->checkUserInLdap($user,$ldap));
        $user->getLdapUserProperties()->setLdapDn('uid=unitTest100,o=unitTest,dc=example,dc=com');
        $this->assertEquals(null,$ldapUserService->checkUserInLdap($user,$ldap));
        $ldapUserService->deleteUser($user);
        $allUSerNew = $userRepository->findUsersfromLdapService();
        foreach ($allUSerNew as $data){

            $this->assertEquals(sizeof($allUSers)-1,sizeof($data->getAddressbook()));
        }
    }
    public function testremoveUserFromLdap(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389', 'uid=admin,ou=system', 'password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl('ldap://localhost:10389');
        $ldapType->setSerVerId('Server1');
        $ldapType->setPassword('password');
        $ldapType->setScope('sub');
        $ldapType->setMapper(array("firstName" => "givenName", "lastName" => "sn", "email" => "uid"));
        $ldapType->setSpecialFields(array("ou" => "ou", "departmentNumber" => "departmentNumber"));
        $ldapType->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapType->setBindType('none');
        $ldapType->setRdn('uid');
        $ldapType->setLdap($ldap);
        $ldapType->setObjectClass('person,organizationalPerson,user');
        $ldapType->setUserNameAttribute('uid');
        $entry = $ldapConnection->retrieveUser($ldap,'o=unitTest,dc=example,dc=com','person,organizationalPerson,user','sub');
        $users = array();
        foreach ($entry as $data){
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data,$ldapType);
        }
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(3,sizeof($users));
        $user = $userRepository->findOneBy(array('username'=>'unitTest1'));
        $user->getLdapUserProperties()->setLdapDn('uid=unitTest100,o=unitTest,dc=example,dc=com');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $ldapUserService->syncDeletedUser($ldap,$ldapType);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(2,sizeof($users));
    }
}
