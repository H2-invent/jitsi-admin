<?php

namespace App\Tests\Command;

use App\Command\CalloutStatistiksCommand;
use App\Entity\CalloutSession;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CalloutStatistiksCommandTest extends KernelTestCase
{
    public function testListsActiveCalloutSessions(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $invitedFrom = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);

        $session = new CalloutSession();
        $session->setRoom($room)
            ->setUser($user)
            ->setInvitedFrom($invitedFrom)
            ->setCreatedAt(new \DateTime())
            ->setUid(md5(uniqid()))
            ->setState(CalloutSession::$INITIATED)
            ->setLeftRetries(2)
            ->setLastDialed(null);
        $em->persist($session);
        $em->flush();

        $command = self::getContainer()->get(CalloutStatistiksCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('Room', $display);
        self::assertStringContainsString('Invited From', $display);
        self::assertStringContainsString('TestMeeting: 0', $display);
        self::assertStringContainsString('test@local.de', $display);
        self::assertStringContainsString('INITIATED', $display);
    }

    public function testShowsHeaderWhenNoCalloutSessionsExist(): void
    {
        self::bootKernel();

        $command = self::getContainer()->get(CalloutStatistiksCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Room', $tester->getDisplay());
    }
}
