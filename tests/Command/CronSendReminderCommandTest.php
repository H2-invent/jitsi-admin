<?php

namespace App\Tests\Command;

use App\Command\CronSendReminderCommand;
use App\Entity\Rooms;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CronSendReminderCommandTest extends KernelTestCase
{
    private function createRoomStartingInFiveMinutes(string $hostUrl): Rooms
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('+5 minutes');
        $end = (clone $start)->modify('+60 minutes');

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Reminder command test');
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->setUid('reminderCommand' . md5($hostUrl));
        $room->setUidReal(md5(uniqid()));
        $room->setSlug('reminderCommand');
        $room->setScheduleMeeting(false);
        $room->setName('Reminder Command Room');
        $room->setSequence(0);
        $room->setServer($server);
        $room->setHostUrl($hostUrl);
        $em->persist($room);
        $em->flush();

        return $room;
    }

    public function testSendsReminderForMatchingHostUrl(): void
    {
        self::bootKernel();
        $hostUrl = 'http://reminder-command-test.local';
        $this->createRoomStartingInFiveMinutes($hostUrl);

        $command = self::getContainer()->get(CronSendReminderCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['--host_url' => $hostUrl]);

        $display = $tester->getDisplay();
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('You activate the Host Fiter', $display);
        self::assertStringContainsString('We select Rooms with ' . $hostUrl, $display);
        self::assertStringContainsString('Hinweis: Cron ok', $display);
        self::assertStringContainsString('Konferenzen: 1', $display);
        self::assertStringContainsString('Emails: 1', $display);
        self::assertStringContainsString('Erfolgreich versandt', $display);
    }

    public function testSendsReminderForUnknownHostUrl(): void
    {
        self::bootKernel();

        $command = self::getContainer()->get(CronSendReminderCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['--host_url' => 'http://no-room-uses-this.local']);

        $display = $tester->getDisplay();
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Konferenzen: 0', $display);
        self::assertStringContainsString('Emails: 0', $display);
        self::assertStringContainsString('Erfolgreich versandt', $display);
    }

    public function testNullHostUrlFilterSelectsRoomsWithEmptyHostUrl(): void
    {
        self::bootKernel();

        $command = self::getContainer()->get(CronSendReminderCommand::class);
        $tester = new CommandTester($command);
        $tester->execute(['--host_url' => 'null']);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('We select Rooms with an empty hostUrl', $tester->getDisplay());
    }
}
