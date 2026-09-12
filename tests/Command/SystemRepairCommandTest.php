<?php

namespace App\Tests\Command;

use App\Command\SystemRepairCommand;
use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class SystemRepairCommandTest extends KernelTestCase
{
    private string $tempDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->originalCwd = getcwd();
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-repair-' . uniqid();
        (new Filesystem())->mkdir($this->tempDir);
        chdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        (new Filesystem())->remove($this->tempDir);
        parent::tearDown();
    }

    public function testEmailWithLeadingWhitespaceIsRepaired(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail('  repair-email@test.local');
        $user->setUuid(md5(uniqid('', true)));
        $user->setPassword('test');
        $user->setCreatedAt(new \DateTime());
        $em->persist($user);
        $em->flush();
        $id = $user->getId();
        self::assertSame('  repair-email@test.local', $user->getEmail());

        $commandTester = $this->runRepair();
        $this->assertStringContainsString('We try to repair the system.....', $commandTester->getDisplay());
        $this->assertStringContainsString('was corrupt', $commandTester->getDisplay());

        $em->clear();
        $repaired = $em->find(User::class, $id);
        self::assertNotNull($repaired);
        self::assertSame('repair-email@test.local', $repaired->getEmail());
    }

    public function testParticipantsAreRemovedFromRoomWithoutModerator(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $user = new User();
        $user->setEmail('repair-room-user@test.local');
        $user->setUuid(md5(uniqid('', true)));
        $user->setPassword('test');
        $user->setCreatedAt(new \DateTime());
        $em->persist($user);

        $room = new Rooms();
        $room->setName('Repair Room Without Moderator');
        $room->setServer($server);
        $room->setUid('repair-no-moderator-' . md5(uniqid('', true)));
        $room->setDuration(60);
        $room->setSequence(0);
        $room->setTimeZone('Europe/Berlin');
        $room->setModerator(null);
        $room->addUser($user);
        $em->persist($room);
        $em->flush();
        $roomId = $room->getId();
        self::assertSame(1, $room->getUser()->count());

        $commandTester = $this->runRepair();
        $this->assertMatchesRegularExpression('/We found \d+ coruppt datasets/', $commandTester->getDisplay());

        $em->clear();
        $repairedRoom = $roomRepo->find($roomId);
        self::assertNotNull($repairedRoom);
        self::assertSame(0, $repairedRoom->getUser()->count());
    }

    public function testOldLobbyWaitingUsersAreRemoved(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $moderator = new User();
        $moderator->setEmail('repair-lobby-moderator@test.local');
        $moderator->setUuid(md5(uniqid('', true)));
        $moderator->setPassword('test');
        $moderator->setCreatedAt(new \DateTime());
        $em->persist($moderator);

        $room = new Rooms();
        $room->setName('Repair Lobby Room');
        $room->setServer($server);
        $room->setUid('repair-lobby-' . md5(uniqid('', true)));
        $room->setDuration(60);
        $room->setSequence(0);
        $room->setTimeZone('Europe/Berlin');
        $room->setModerator($moderator);
        $room->addUser($moderator);
        $em->persist($room);

        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setUser($moderator);
        $lobbyUser->setRoom($room);
        $lobbyUser->setUid(md5(uniqid('', true)));
        $lobbyUser->setType('a');
        $lobbyUser->setShowName('Repair Lobby User');
        $lobbyUser->setCreatedAt((new \DateTime())->modify('-11 days'));
        $em->persist($lobbyUser);
        $em->flush();
        $lobbyUserId = $lobbyUser->getId();

        $this->runRepair();

        $em->clear();
        self::assertNull($em->find(LobbyWaitungUser::class, $lobbyUserId));
    }

    private function runRepair(): CommandTester
    {
        $commandTester = new CommandTester(self::getContainer()->get(SystemRepairCommand::class));
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('We clear the cache', $commandTester->getDisplay());

        return $commandTester;
    }
}
