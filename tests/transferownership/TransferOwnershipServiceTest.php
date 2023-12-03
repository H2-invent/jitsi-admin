<?php

namespace App\Tests\transferownership;

use App\Repository\RoomsRepository;
use App\Repository\RoomsUserRepository;
use App\Repository\UserRepository;
use App\Service\TransferOwnershipService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function PHPUnit\Framework\assertEquals;

class TransferOwnershipServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();


        $sut = self::getContainer()->get(TransferOwnershipService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name'=>'TestMeeting: 0']);
        $oldModerator = $room->getModerator();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email'=>'test@local2.de']);
        $newRoom = $sut->transferOwnership($user,$room);
        assertEquals($user,$newRoom->getModerator());
        assertEquals($user,$newRoom->getCreator());
        $roomUserRepo = self::getContainer()->get(RoomsUserRepository::class);
        $userRoom = $roomUserRepo->findOneBy(['user'=>$oldModerator,'room'=>$room]);
        self::assertTrue($userRoom->getModerator());
        self::assertTrue($userRoom->getLobbyModerator());
    }
}
