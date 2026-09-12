<?php

namespace App\Tests\SipCaller;

use App\Command\CallerPrepareCommand;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CallerPrepareCommandTest extends KernelTestCase
{
    public function testAddsCallerIdToFutureRoomWithoutOne(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('+1 day');
        $end = (clone $start)->modify('+60 minutes');
        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Caller prepare command test');
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->setUid('callerPrepare' . md5(uniqid()));
        $room->setUidReal(md5(uniqid()));
        $room->setSlug('callerPrepare');
        $room->setScheduleMeeting(false);
        $room->setName('Caller Prepare Command Room');
        $room->setSequence(0);
        $room->setServer($server);
        $em->persist($room);
        $em->flush();
        $roomId = $room->getId();
        self::assertNull($room->getCallerRoom());

        $command = self::getContainer()->get(CallerPrepareCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('We added to all Rooms which had no caller-Id a caller-Id', $tester->getDisplay());

        $em->clear();
        $reloaded = self::getContainer()->get(RoomsRepository::class)->find($roomId);
        self::assertNotNull($reloaded->getCallerRoom());
        self::assertMatchesRegularExpression('/^\d{6}$/', $reloaded->getCallerRoom()->getCallerId());
    }
}
