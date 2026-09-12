<?php

namespace App\Tests\Twig;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Twig\RoomsInFuture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFilter;

class RoomsInFutureTest extends KernelTestCase
{
    private function createRoom(
        EntityManagerInterface $em,
        Server $server,
        string $name,
        \DateTimeInterface $start,
        bool $showOnJoinpage,
        bool $withModerator
    ): Rooms {
        $room = new Rooms();
        $room->setName($name)
            ->setServer($server)
            ->setUid('twig-future-' . uniqid())
            ->setDuration(60)
            ->setSequence(0)
            ->setTimeZone('UTC')
            ->setShowRoomOnJoinpage($showOnJoinpage)
            ->setStart($start)
            ->setEnddate((clone $start)->modify('+60min'));

        if ($withModerator) {
            $moderator = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
            $room->setModerator($moderator);
        }

        $em->persist($room);
        $em->flush();

        return $room;
    }

    public function testGetFiltersReturnsExpectedTwigFilter(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(RoomsInFuture::class);

        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);
        $this->assertInstanceOf(TwigFilter::class, $filters[0]);
        $this->assertSame('roomsinFuture', $filters[0]->getName());
        $this->assertSame([$extension, 'roomsinFuture'], $filters[0]->getCallable());
    }

    public function testRoomsinFutureReturnsOnlyMatchingRooms(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(RoomsInFuture::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si2']);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $expected = $this->createRoom($em, $server, 'Twig Future Room', (clone $now)->modify('+2 days'), true, true);
        $hidden = $this->createRoom($em, $server, 'Twig Future Room Hidden', (clone $now)->modify('+2 days'), false, true);
        $past = $this->createRoom($em, $server, 'Twig Past Room', (clone $now)->modify('-2 days'), true, true);
        $withoutModerator = $this->createRoom($em, $server, 'Twig Future Room No Moderator', (clone $now)->modify('+2 days'), true, false);

        $result = $extension->roomsinFuture($server);
        $ids = array_map(static fn(Rooms $room) => $room->getId(), $result);

        $this->assertContains($expected->getId(), $ids);
        $this->assertNotContains($hidden->getId(), $ids);
        $this->assertNotContains($past->getId(), $ids);
        $this->assertNotContains($withoutModerator->getId(), $ids);
    }

    public function testRoomsinFutureOrdersByStart(): void
    {
        self::bootKernel();
        $extension = self::getContainer()->get(RoomsInFuture::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si2']);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        $later = $this->createRoom($em, $server, 'Twig Later Room', (clone $now)->modify('+5 days'), true, true);
        $sooner = $this->createRoom($em, $server, 'Twig Sooner Room', (clone $now)->modify('+1 day'), true, true);

        $result = call_user_func($extension->getFilters()[0]->getCallable(), $server);
        $positions = [];
        foreach ($result as $index => $room) {
            if ($room->getId() === $sooner->getId()) {
                $positions['sooner'] = $index;
            }
            if ($room->getId() === $later->getId()) {
                $positions['later'] = $index;
            }
        }

        $this->assertArrayHasKey('sooner', $positions);
        $this->assertArrayHasKey('later', $positions);
        $this->assertLessThan($positions['later'], $positions['sooner']);
    }
}
