<?php

namespace App\Tests\LDAP;

use App\dataType\LdapType;
use App\Repository\UserRepository;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapUserService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserShowTest extends WebTestCase
{
    public $LDAPURL = 'ldap://192.168.230.128:10389';
    public function testShowName(): void
    {
        $client = static::createClient();


        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();
        $this->getParam();
        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl($this->LDAPURL);
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
        $ldapType->setFilter('(&(mail=*))');
        $ldapType->createLDAP();
        $entry = $ldapConnection->retrieveUser($ldapType);

        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $ldapUserService->connectUserwithAllUSersInAdressbock();
        $ldapUserService->cleanUpAdressbook();
        //login as the ldap user and test if the name in the adressbook is written correctly

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(array('username' => 'unitTest1Sub'));
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            1,
        $crawler->filter('.breakWord:contains("Reh, Rainer")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.breakWord:contains("AA, 45689, Hase, Hans")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.breakWord:contains("AA, 45689, Maus, Maike")')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.breakWord:contains("AA, 45689, Forelle, Frieder")')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.breakWord:contains("unitTest")')->count()
        );



    }
    public function testShowName2(): void
    {
        $client = static::createClient();


        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();
        $this->getParam();
        // (3) run some service & test the result
        $ldapConnection = $container->get(LdapService::class);
        $ldapUserService = $container->get(LdapUserService::class);
        $ldap = $ldapConnection->createLDAP($this->LDAPURL, 'uid=admin,ou=system', 'password');
        $ldapType = new LdapType($ldapConnection);
        $ldapType->setUrl($this->LDAPURL);
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
        $ldapType->setFilter('(&(mail=*))');
        $ldapType->createLDAP();
        $entry = $ldapConnection->retrieveUser($ldapType);

        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $ldapUserService->connectUserwithAllUSersInAdressbock();
        $ldapUserService->cleanUpAdressbook();
        //login as the ldap user and test if the name in the adressbook is written correctly

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser2 = $userRepository->findOneBy(array('username' => 'unitTest1'));
        $client->loginUser($testUser2);
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertResponseIsSuccessful();
        $this->assertEquals(
            1,
            $crawler->filter('.breakWord:contains("Reh, Rainer")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.breakWord:contains("AA, 45689, Hase, Hans")')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.breakWord:contains("AA, 45689, Maus, Maike")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.breakWord:contains("AA, 45689, Forelle, Frieder")')->count()
        );
        $this->assertEquals(
            0,
            $crawler->filter('.breakWord:contains("unitTest")')->count()
        );

    }
    private function getParam()
    {
        $para = self::getContainer()->get(ParameterBagInterface::class);
        $this->LDAPURL = $para->get('ldap_test_url');
    }
}
