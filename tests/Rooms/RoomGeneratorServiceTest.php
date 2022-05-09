<?php

namespace App\Tests\Rooms;

use App\Repository\ServerRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Service\RoomGeneratorService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomGeneratorServiceTest extends KernelTestCase
{
    public function testnoServer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $roomGen = self::getContainer()->get(RoomGeneratorService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $tagRepo = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepo->findOneBy(array('title'=>'Test Tag Enabled'));
        $user = $userRepo->findOneBy(array('username'=>'test@local.de'));
        $room = $roomGen->createRoom($user);
        self::assertTrue($room->getLobby());
        self::assertFalse($room->getPersistantRoom());
        self::assertEquals($user,$room->getModerator());
        self::assertEquals(array($user),$room->getUser()->toArray());
        self::assertNull($room->getServer());
        self::assertEquals('Europe/Berlin',$room->getTimeZone());
        self::assertEquals($tag, $room->getTag());
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testServer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $roomGen = self::getContainer()->get(RoomGeneratorService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('username'=>'test@local.de'));
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(array('url'=>'meet.jit.si'));
        $room = $roomGen->createRoom($user,$server);
        self::assertTrue($room->getLobby());
        self::assertFalse($room->getPersistantRoom());
        self::assertEquals($user,$room->getModerator());
        self::assertEquals(array($user),$room->getUser()->toArray());
        self::assertEquals($server, $room->getServer());
        self::assertEquals('Europe/Berlin',$room->getTimeZone());
        //$routerService = static::getContainer()->get('router');
        //$myCustomService = static::getContainer()->get(CustomService::class);
    }
}
