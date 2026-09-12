<?php

namespace App\Tests\Command;

use App\Command\MigrateToAdressbookCommand;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateToAdressbookCommandTest extends KernelTestCase
{
    public function testParticipantsOfModeratedRoomsAreAddedToAddressbook(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']);

        $moderator = $this->createUser($em, 'addressbook-moderator@test.local');
        $participant = $this->createUser($em, 'addressbook-participant@test.local');
        $em->flush();

        $room = new Rooms();
        $room->setName('Addressbook Migration Room');
        $room->setServer($server);
        $room->setUid('addressbook-migration-' . md5(uniqid('', true)));
        $room->setDuration(60);
        $room->setSequence(0);
        $room->setTimeZone('Europe/Berlin');
        $moderator->addRoomModerator($room);
        $room->addUser($moderator);
        $room->addUser($participant);
        $em->persist($room);
        $em->flush();
        $moderatorId = $moderator->getId();
        $participantId = $participant->getId();

        $commandTester = new CommandTester(self::getContainer()->get(MigrateToAdressbookCommand::class));
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertMatchesRegularExpression('/You genereated \d+ Adressentries with \d+ Users/', $commandTester->getDisplay());

        $em->clear();
        $moderator = $userRepo->find($moderatorId);
        $participant = $userRepo->find($participantId);
        self::assertNotNull($moderator);
        self::assertNotNull($participant);
        self::assertTrue($moderator->getAddressbook()->contains($participant));
        self::assertSame(1, $moderator->getAddressbook()->count());
    }

    private function createUser(EntityManagerInterface $em, string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUuid(md5(uniqid('', true)));
        $user->setPassword('test');
        $user->setCreatedAt(new \DateTime());
        $em->persist($user);

        return $user;
    }
}
