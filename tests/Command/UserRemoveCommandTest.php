<?php

namespace App\Tests\Command;

use App\Command\UserRemoveCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class UserRemoveCommandTest extends KernelTestCase
{
    public function testRemovesUserByUsername(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $username = 'phpunit-remove-' . uniqid() . '@local.de';

        $user = new User();
        $user->setEmail($username);
        $user->setUsername($username);
        $user->setFirstName('Remove');
        $user->setLastName('Me');
        $user->setCreatedAt(new \DateTime());
        $user->setRegisterId(1);
        $em->persist($user);
        $em->flush();

        try {
            $command = self::getContainer()->get(UserRemoveCommand::class);
            $tester = new CommandTester($command);
            $tester->execute(['username' => $username]);

            self::assertSame(Command::SUCCESS, $tester->getStatusCode());
            $display = $tester->getDisplay();
            self::assertStringContainsString('You passed an argument: ' . $username, $display);
            self::assertStringContainsString('Remove the User ' . $username, $display);

            $em->clear();
            self::assertNull(self::getContainer()->get(UserRepository::class)->findOneBy(['username' => $username]));
        } finally {
            $em->clear();
            $stillThere = self::getContainer()->get(UserRepository::class)->findOneBy(['username' => $username]);
            if ($stillThere !== null) {
                $em->remove($stillThere);
                $em->flush();
            }
        }
    }
}
