<?php

namespace App\Tests;

use App\dataType\LdapType;
use App\Repository\UserRepository;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapUserService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserShowTest extends WebTestCase
{
    public function testShowName(): void
    {
        $client = static::createClient();


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
        $entry = $ldapConnection->retrieveUser($ldap, 'o=unitTest,dc=example,dc=com', 'person,organizationalPerson,user', 'sub');

        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $ldapUserService->connectUserwithAllUSersInAdressbock();
        //login as the ldap user and test if the name in the adressbook is written correctly

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(array('username' => 'unitTest1Sub'));
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.breakWord', ', , Hase, Hans');
        $this->assertSelectorTextNotContains('.breakWord', 'unitTest2');
    }
}
