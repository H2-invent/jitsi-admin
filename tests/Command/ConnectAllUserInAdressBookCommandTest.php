<?php

namespace App\Tests\Command;

use App\Command\ConnectAllUserInAdressBookCommand;
use App\Entity\LdapUserProperties;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ConnectAllUserInAdressBookCommandTest extends KernelTestCase
{
    private function createLdapUser(EntityManagerInterface $em, string $email): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setUsername($email)
            ->setCreatedAt(new \DateTime())
            ->setUid(md5($email));
        $em->persist($user);
        $properties = (new LdapUserProperties())
            ->setUser($user)
            ->setLdapHost('ldap://test.local')
            ->setLdapDn('cn=' . $email . ',dc=example,dc=com')
            ->setLdapNumber('ldap_1');
        $em->persist($properties);
        $em->flush();

        return $user;
    }

    public function testConnectAllLdapUsersInAddressbook(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $ldapUser = $this->createLdapUser($em, 'secondLdap@local.de');

        $firstLdapUser = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'ldapUser@local.de']);
        $this->assertNotNull($firstLdapUser);

        $command = self::getContainer()->get(ConnectAllUserInAdressBookCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('We connect 2 user in the adressbook', $tester->getDisplay());

        $em->refresh($firstLdapUser);
        $em->refresh($ldapUser);

        $this->assertTrue($firstLdapUser->getAddressbook()->contains($ldapUser));
        $this->assertFalse($firstLdapUser->getAddressbook()->contains($firstLdapUser));
        $this->assertTrue($ldapUser->getAddressbook()->contains($firstLdapUser));
        $this->assertFalse($ldapUser->getAddressbook()->contains($ldapUser));
    }
}
