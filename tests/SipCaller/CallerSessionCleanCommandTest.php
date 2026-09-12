<?php

namespace App\Tests\SipCaller;

use App\Entity\CallerId;
use App\Entity\CallerSession;
use App\Repository\CallerSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CallerSessionCleanCommandTest extends KernelTestCase
{
    private function createCallerSession(): CallerSession
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);

        $callerId = new CallerId();
        $callerId->setRoom($room)
            ->setUser($user)
            ->setCallerId('987654321')
            ->setCreatedAt(new \DateTime());
        $em->persist($callerId);

        $session = new CallerSession();
        $session->setSessionId(md5(uniqid('', true)))
            ->setCreatedAt(new \DateTime())
            ->setAuthOk(false)
            ->setShowName('Caller Session Test')
            ->setCallerId('987654321')
            ->setCaller($callerId);
        $em->persist($session);
        $em->flush();

        return $session;
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::bootKernel());
        $command = $application->find('app:caller:session:clean');

        return new CommandTester($command);
    }

    public function testDeletesSessionWhenConfirmed(): void
    {
        self::bootKernel();
        $session = $this->createCallerSession();
        $sessionId = $session->getId();
        $sessionKey = $session->getSessionId();

        $tester = $this->commandTester();
        $tester->setInputs([(string)$sessionId, 'yes']);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('Caller Session Test', $display);
        self::assertStringContainsString(sprintf('Delete Session %s from %s', $sessionKey, 'Caller Session Test'), $display);

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertNull(self::getContainer()->get(CallerSessionRepository::class)->find($sessionId));
    }

    public function testKeepsSessionWhenNotConfirmed(): void
    {
        self::bootKernel();
        $session = $this->createCallerSession();
        $sessionId = $session->getId();

        $tester = $this->commandTester();
        $tester->setInputs([(string)$sessionId, 'no']);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('NOT deleting the session', $tester->getDisplay());

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        self::assertNotNull(self::getContainer()->get(CallerSessionRepository::class)->find($sessionId));
    }

    public function testFailsForUnknownSessionId(): void
    {
        self::bootKernel();

        $tester = $this->commandTester();
        $tester->setInputs(['999999999']);
        $tester->execute([]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('No such ID', $tester->getDisplay());
    }
}
