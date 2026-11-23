<?php

namespace App\Tests\Twig;

use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\Server;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Twig\CheckRoomPermissions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class CheckRoomPermissionsTest extends KernelTestCase
{

    private function functionNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function filterNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFilter::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function createRoom(EntityManagerInterface $em, Server $server): Rooms
    {
        $room = new Rooms();
        $room->setName('Twig Permissions Room ' . uniqid())
            ->setServer($server)
            ->setUid('twig-perm-' . uniqid())
            ->setDuration(60)
            ->setSequence(0);
        $em->persist($room);
        $em->flush();

        return $room;
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = self::getContainer()->get(CheckRoomPermissions::class);
        $this->assertSame(["roomPermissions"], $this->functionNames($extension));
    }

    public function testRoomPermissionsReturnsEmptyObjectWhenNoEntryExists(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(CheckRoomPermissions::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si2']);
        $room = $this->createRoom($em, $server);

        $permissions = $extension->roomPermissions($user, $room);

        $this->assertInstanceOf(RoomsUser::class, $permissions);
        $this->assertNull($permissions->getId());
        $this->assertNull($permissions->getModerator());
    }

    public function testRoomPermissionsReturnsPersistedEntry(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(CheckRoomPermissions::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si2']);
        $room = $this->createRoom($em, $server);

        $roomsUser = new RoomsUser();
        $roomsUser->setUser($user)
            ->setRoom($room)
            ->setModerator(true)
            ->setLobbyModerator(false)
            ->setPrivateMessage(true);
        $em->persist($roomsUser);
        $em->flush();

        $permissions = call_user_func([$extension, 'roomPermissions'], $user, $room);

        $this->assertInstanceOf(RoomsUser::class, $permissions);
        $this->assertSame($roomsUser->getId(), $permissions->getId());
        $this->assertSame($user->getId(), $permissions->getUser()->getId());
        $this->assertSame($room->getId(), $permissions->getRoom()->getId());
        $this->assertTrue($permissions->getModerator());
        $this->assertTrue($permissions->getPrivateMessage());
    }
}
