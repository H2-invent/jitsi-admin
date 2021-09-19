<?php

namespace App\Tests;

use App\dataType\LdapType;
use App\Service\ldap\LdapService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LdapConnectionTest extends KernelTestCase
{
    public function testConnectionOhneLogin(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389','','',true);
        $this->assertEquals(3, $ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))',array('scope'=>'sub'))->execute()->count());
    }
    public function testConnectionMitLogin(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389','uid=admin,ou=system','password');
        $this->assertEquals(3,$ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))',array('scope'=>'sub'))->execute()->count());
    }
    public function testConnectionMitLoginOne(): void
    {
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389','uid=admin,ou=system','password');
        $this->assertEquals(2,$ldap->query('o=unitTest,dc=example,dc=com', '(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))',array('scope'=>'one'))->execute()->count());
    }
    public function createObjectClass(){
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389','uid=admin,ou=system','password');

        $this->assertEquals('(&(|(objectclass=person)(objectclass=organizationalPerson)(objectclass=user)))',$ldapConnection->buildObjectClass(array('person','organizationalPerson','user')));

    }
    public function createFetchUserOne(){
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389','uid=admin,ou=system','password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl('ldap://localhost:10389');
        $ldapType->setSerVerId('Server1');
        $ldapType->setPassword('password');
        $ldapType->setScope('sub');
        $ldapType->setMapper('{"firstName":"givenName", "lastName":"sn", "email":"uid"}');
        $ldapType->setSpecialFields('{"ou":"ou","departmentNumber":"departmentNumber"}');
        $ldapType->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapType->setBindType('none');
        $ldapType->setRdn('uid');
        $ldapType->setLdap($ldap);

        $this->assertEquals(3,$ldapConnection->fetchLdap($ldapType)['user']);

    }
    public function createFetchUserSub(){
        // (1) boot the Symfony kernel
        self::bootKernel();

        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldap = $ldapConnection->createLDAP('ldap://localhost:10389','uid=admin,ou=system','password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl('ldap://localhost:10389');
        $ldapType->setSerVerId('Server1');
        $ldapType->setPassword('password');
        $ldapType->setScope('one');
        $ldapType->setMapper('{"firstName":"givenName", "lastName":"sn", "email":"uid"}');
        $ldapType->setSpecialFields('{"ou":"ou","departmentNumber":"departmentNumber"}');
        $ldapType->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapType->setBindType('none');
        $ldapType->setRdn('uid');
        $ldapType->setLdap($ldap);

        $this->assertEquals(2,$ldapConnection->fetchLdap($ldapType)['user']);

    }
}
