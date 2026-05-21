<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Service\JoinService;
use App\Service\RoomService;
use App\UtilsHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $roomService = $this->getContainer()->get(RoomService::class);
        $res = $roomService->generateJwt($room, null, 'Test User');
        self::assertEquals('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMSJ9LCJ1c2VyIjp7Im5hbWUiOiJUZXN0IFVzZXIiLCJsYW5ndWFnZSI6ImRlIiwidGltZXpvbmUiOiJFdXJvcGUvQmVybGluIn19LCJtb2RlcmF0b3IiOmZhbHNlLCJ0aGVtZSI6eyJjb2xvclNjaGVtZSI6ImxpZ2h0In19.kqp93y3a-OcYxhdAcegEafF83Zs5sw-MDLljRJB48eQ', $res);
        $res = $roomService->join($room, $room->getModerator(), 'a', 'Test User');
        $slugyfy = UtilsHelper::slugify($room->getName());
        $this->assertEquals('jitsi-meet://meet.jit.si2/123456781?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMSJ9LCJ1c2VyIjp7Im5hbWUiOiJUZXN0IFVzZXIiLCJsYW5ndWFnZSI6ImRlIiwidGltZXpvbmUiOiJFdXJvcGUvQmVybGluIn19LCJtb2RlcmF0b3IiOnRydWUsInRoZW1lIjp7ImNvbG9yU2NoZW1lIjoibGlnaHQifX0.Wwu4p9wmiuvF6v_fQS9QkT3C1fDAoVZJ71tMliA0eBs#config.subject=%22' . $slugyfy . '%22', $res);
        $res = $roomService->join($room, $room->getModerator(), 'b', 'Test User');
        $this->assertEquals('https://meet.jit.si2/123456781?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMSJ9LCJ1c2VyIjp7Im5hbWUiOiJUZXN0IFVzZXIiLCJsYW5ndWFnZSI6ImRlIiwidGltZXpvbmUiOiJFdXJvcGUvQmVybGluIn19LCJtb2RlcmF0b3IiOnRydWUsInRoZW1lIjp7ImNvbG9yU2NoZW1lIjoibGlnaHQifX0.Wwu4p9wmiuvF6v_fQS9QkT3C1fDAoVZJ71tMliA0eBs#config.subject=%22'. $slugyfy . '%22', $res);
    }
}
