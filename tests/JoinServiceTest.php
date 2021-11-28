<?php

namespace App\Tests;

use App\Repository\RoomsRepository;
use App\Service\JoinService;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;

class JoinServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findAll()[0];
        $joinService = $this->getContainer()->get(JoinService::class);
        $room->setOnlyRegisteredUsers(true);
        self::assertEquals(true, $joinService->onlyWithUserAccount($room));
        $room->setOnlyRegisteredUsers(false);
        self::assertEquals(false, $joinService->onlyWithUserAccount($room));
        self::assertEquals(false, $joinService->onlyWithUserAccount(null));
    }

    public function testJwtToken(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 1'));
        $roomService = $this->getContainer()->get(RoomService::class);
        $res = $roomService->join($room,$room->getModerator(),'a','Test User');
        $this->assertEquals('jitsi-meet://meet.jit.si2/123456781?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiJUZXN0IFVzZXIifX0sIm1vZGVyYXRvciI6dHJ1ZX0.hiLi5WUh0mMh972m_6NdPUhk7jQyxUkthVUIs9ZECno#config.subject=%22TestMeeting: 1%22',$res);
        $res = $roomService->join($room,$room->getModerator(),'b','Test User');
        $this->assertEquals('https://meet.jit.si2/123456781?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJ1c2VyIjp7Im5hbWUiOiJUZXN0IFVzZXIifX0sIm1vZGVyYXRvciI6dHJ1ZX0.hiLi5WUh0mMh972m_6NdPUhk7jQyxUkthVUIs9ZECno#config.subject=%22TestMeeting: 1%22',$res);

    }
}
