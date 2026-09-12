<?php

namespace App\Tests\Command;

use App\Command\MigrateTimeZoneCommand;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateTimeZoneCommandTest extends KernelTestCase
{
    public function testMigrationRecomputesUtcFromLocalTime(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $timeZone = new \DateTimeZone('Europe/Berlin');
        $start = new \DateTime('2026-01-15 10:00:00', $timeZone);
        $end = new \DateTime('2026-01-15 11:30:00', $timeZone);
        $expectedStartTimestamp = $start->getTimestamp();
        $expectedEndTimestamp = $end->getTimestamp();

        $room = new Rooms();
        $room->setName('Migrate TimeZone Room');
        $room->setServer($server);
        $room->setUid('migrate-timezone-' . md5(uniqid('', true)));
        $room->setDuration(60);
        $room->setSequence(0);
        $room->setTimeZone('Europe/Berlin');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setStartUtc(null);
        $room->setEndDateUtc(null);
        $room->setStartTimestamp(null);
        $room->setEndTimestamp(null);
        $em->persist($room);
        $em->flush();
        $id = $room->getId();

        $commandTester = new CommandTester(self::getContainer()->get(MigrateTimeZoneCommand::class));
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $em->clear();
        $migrated = $roomRepo->find($id);
        self::assertNotNull($migrated);
        self::assertSame('2026-01-15 09:00:00', $migrated->getStartUtc()->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-15 10:30:00', $migrated->getEndDateUtc()->format('Y-m-d H:i:s'));
        self::assertSame($expectedStartTimestamp, $migrated->getStartTimestamp());
        self::assertSame($expectedEndTimestamp, $migrated->getEndTimestamp());
    }
}
