<?php

namespace App\Tests\Whiteboard;

use App\Repository\RoomsRepository;
use App\Service\Whiteboard\WhiteboardJwtService;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WhiteBoardJwtServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $whiteboardService = self::getContainer()->get(WhiteboardJwtService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        self::assertEquals(JWT::encode([
            'iat' => (new \DateTime())->getTimestamp(),
            'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
            'roles' => array('editor:'.$room->getUidReal())
        ],'MY_SECRET'),
            $whiteboardService->createJwt($room)
        );
        self::assertEquals(JWT::encode([
            'iat' => (new \DateTime())->getTimestamp(),
            'exp' => (new \DateTime())->modify('+3days')->getTimestamp(),
            'roles' => array('moderator:'.$room->getUidReal())
        ],'MY_SECRET'),
            $whiteboardService->createJwt($room,true)
        );

    }
}
