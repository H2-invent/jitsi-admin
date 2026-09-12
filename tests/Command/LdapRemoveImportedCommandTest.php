<?php

namespace App\Tests\Command;

use App\Command\LdapRemoveImportedCommand;
use App\Entity\LdapUserProperties;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LdapRemoveImportedCommandTest extends KernelTestCase
{
    private function createLdapUser(EntityManagerInterface $em, string $email, string $ldapNumber): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setUsername($email)
            ->setFirstName('Ldap')
            ->setLastName('Removable')
            ->setCreatedAt(new \DateTime())
            ->setUid(md5($email));
        $properties = (new LdapUserProperties())
            ->setLdapHost('ldap://test.local')
            ->setLdapDn('cn=' . $email . ',dc=example,dc=com')
            ->setLdapNumber($ldapNumber);
        $user->setLdapUserProperties($properties);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testAbortDeletesNothing(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $this->createLdapUser($em, 'abort@local.de', 'ldap_1');

        $command = self::getContainer()->get(LdapRemoveImportedCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs([0, 'no']);
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Aborted', $tester->getDisplay());
        $this->assertNotNull(self::getContainer()->get(UserRepository::class)->find($user->getId()));
    }

    public function testInvalidServerSelectionFails(): void
    {
        self::bootKernel();
        $command = self::getContainer()->get(LdapRemoveImportedCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs([99]);
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('This number not machting a server Id', $tester->getDisplay());
    }

    public function testDeleteUsersOfSelectedLdapServer(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = $this->createLdapUser($em, 'deleteMe@local.de', 'ldap_1');
        $otherUser = $this->createLdapUser($em, 'keepMe@local.de', 'ldap_2');
        $deletedId = $user->getId();
        $keptId = $otherUser->getId();

        $command = self::getContainer()->get(LdapRemoveImportedCommand::class);
        $tester = new CommandTester($command);
        $tester->setInputs([0, 'yes']);
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('we start to delete', $display);
        $this->assertStringContainsString('deleteMe@local.de', $display);

        $repository = self::getContainer()->get(UserRepository::class);
        $this->assertNull($repository->find($deletedId));
        $this->assertNotNull($repository->find($keptId));
    }
}
