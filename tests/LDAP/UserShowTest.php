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

        $ldapUserService = $container->get(LdapUserService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setSerVerId('Server1');
        $ldapConnection->setPassword('password');
        $ldapConnection->setScope('sub');
        $ldapConnection->setMapper(["firstName" => "givenName", "lastName" => "sn", "email" => "uid"]);
        $ldapConnection->setSpecialFields(["ou" => "ou", "departmentNumber" => "departmentNumber"]);
        $ldapConnection->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapConnection->setBindType('none');
        $ldapConnection->setRdn('uid');
        $ldapConnection->setLdap($ldap);
        $ldapConnection->setObjectClass('person,organizationalPerson,user');
        $ldapConnection->setUserNameAttribute('uid');
        $ldapConnection->setFilter('(&(mail=*))');
        $ldapConnection->createLDAP();
        $entry = $ldapConnection->retrieveUser();

        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapConnection);
        }
        $ldapUserService->connectUserwithAllUSersInAdressbock();
        $ldapUserService->cleanUpAdressbook();
        //login as the ldap user and test if the name in the adressbook is written correctly

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['username' => 'unitTest1Sub']);
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
            3,
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

        $ldapUserService = $container->get(LdapUserService::class);
        $ldapService = self::getContainer()->get(LdapService::class);
        $ldapConnection = new LdapType();
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setBindDn('uid=admin,ou=system');
        $ldapConnection->setPassword('password');
        $ldapConnection->setBindType('simple');
        $ldapConnection->createLDAP();
        $ldap = $ldapConnection->getLdap();
        $ldapConnection = new LdapType($ldapConnection);
        $ldapConnection->setUrl($this->LDAPURL);
        $ldapConnection->setSerVerId('Server1');
        $ldapConnection->setPassword('password');
        $ldapConnection->setScope('sub');
        $ldapConnection->setMapper(["firstName" => "givenName", "lastName" => "sn", "email" => "uid"]);
        $ldapConnection->setSpecialFields(["ou" => "ou", "departmentNumber" => "departmentNumber"]);
        $ldapConnection->setUserDn('o=unitTest,dc=example,dc=com');
        $ldapConnection->setBindType('none');
        $ldapConnection->setRdn('uid');
        $ldapConnection->setLdap($ldap);
        $ldapConnection->setObjectClass('person,organizationalPerson,user');
        $ldapConnection->setUserNameAttribute('uid');
        $ldapConnection->setFilter('(&(mail=*))');
        $ldapConnection->createLDAP();
        $entry = $ldapConnection->retrieveUser();

        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapConnection);
        }
        $ldapUserService->connectUserwithAllUSersInAdressbock();
        $ldapUserService->cleanUpAdressbook();
        //login as the ldap user and test if the name in the adressbook is written correctly

        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser2 = $userRepository->findOneBy(['username' => 'unitTest1']);
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
            3,
            $crawler->filter('.breakWord:contains("unitTest")')->count()
        );
    }
    private function getParam()
    {
        $para = self::getContainer()->get(ParameterBagInterface::class);
        $this->LDAPURL = $para->get('ldap_test_url');
    }
}
