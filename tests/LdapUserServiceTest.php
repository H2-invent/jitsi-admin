<?php

namespace App\Tests;

use App\dataType\LdapType;
use App\Entity\Rooms;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LdapUserServiceTest extends WebTestCase
{
    public function testRetrieveUserfromDatabasefromUserNameAttribute(): void
    {
        // (1) boot the Symfony kernel
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
        $users = array();
        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $this->assertEquals(LdapConnectionTest::$UserInLDAP, sizeof($users));
        $allUSers = $ldapUserService->connectUserwithAllUSersInAdressbock();
        foreach ($allUSers as $data) {
            $this->assertEquals(sizeof($allUSers) - 1, sizeof($data->getAddressbook()));
        }


        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(array('username' => 'UnitTest1'));
        $this->assertNotEquals(null, $ldapUserService->checkUserInLdap($user, $ldap));
        $user->getLdapUserProperties()->setLdapDn('uid=unitTest100,o=unitTest,dc=example,dc=com');
        $this->assertEquals(null, $ldapUserService->checkUserInLdap($user, $ldap));

        $ldapUserService->deleteUser($user);
        $allUSerNew = $userRepository->findUsersfromLdapService();
        foreach ($allUSerNew as $data) {
            $this->assertEquals(sizeof($allUSerNew) - 1, sizeof($data->getAddressbook()));
        }
        foreach ($allUSerNew as $data) {
            if ($data->getUsername() === 'unitTestnoSF') {
                $this->assertEquals('', $data->getSpezialProperties()['ou']);
                $this->assertEquals('', $data->getSpezialProperties()['departmentNumber']);
            } else {
                $this->assertEquals('AA', $data->getSpezialProperties()['ou']);
                $this->assertEquals('45689', $data->getSpezialProperties()['departmentNumber']);
            }

        }
    }

    public function testRoomShowAttribute(): void
    {
        // (1) boot the Symfony kernel
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
        $users = array();
        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $this->assertEquals(LdapConnectionTest::$UserInLDAP, sizeof($users));
        $allUSers = $ldapUserService->connectUserwithAllUSersInAdressbock();
        foreach ($allUSers as $data) {
            $this->assertEquals(sizeof($allUSers) - 1, sizeof($data->getAddressbook()));
        }
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->flush();
        $userRepository = static::getContainer()->get(UserRepository::class);
        $serverRepository = static::getContainer()->get(ServerRepository::class);
        $server = $serverRepository->findAll()[0];

        $user = $userRepository->findOneBy(array('username' => 'UnitTest1'));
        $user->addServer($server);
        $em->persist($user);
        $em->flush();
        $client->loginUser($user);
        $room = new Rooms();
        $room->setModerator($user);
        $room->addUser($user);
        $room->setStart(new \DateTime());
        $room->setEnddate((new \DateTime())->modify('+60min'));
        $room->setDuration(60);
        $room->setName('testRaum');
        $room->setServer($server);
        $room->setAgenda('Ich bin eine Testagenda');
        $room->setUid('testUid123');
        $room->setPublic(true);
        $room->setSequence(0);
        $em->persist($room);
        $em->flush();
        $crawler = $client->request('GET', '/room/dashboard');
        $this->assertResponseIsSuccessful();

        $this->assertEquals(
            1,
            $crawler->filter('.h5-responsive:contains("testRaum")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('p:contains("Organisator: AA, 45689, Maus, Maike")')->count()
        );
        $this->assertEquals(
            1,
            $crawler->filter('.badge:contains("Moderator")')->count()
        );

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
        $entry = $ldapConnection->retrieveUser($ldap, 'o=unitTest,dc=example,dc=com', 'person,organizationalPerson,user', 'sub');
        $users = array();
        foreach ($entry as $data) {
            $users[] = $ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($data, $ldapType);
        }
        $userRepository = static::getContainer()->get(UserRepository::class);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(LdapConnectionTest::$UserInLDAP, sizeof($users));
        $user = $userRepository->findOneBy(array('username' => 'unitTest1'));
        $user->getLdapUserProperties()->setLdapDn('uid=unitTest100,o=unitTest,dc=example,dc=com');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();
        $ldapUserService->syncDeletedUser($ldap, $ldapType);
        $users = $userRepository->findUsersfromLdapService();
        $this->assertEquals(LdapConnectionTest::$UserInLDAP-1, sizeof($users));
    }


}
