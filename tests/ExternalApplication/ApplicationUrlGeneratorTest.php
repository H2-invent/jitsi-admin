<?php

namespace App\Tests\ExternalApplication;

use App\Entity\LobbyWaitungUser;
use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Whiteboard\WhiteboardJwtService;
use App\Twig\ApplicationUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApplicationUrlGeneratorTest extends KernelTestCase
{
    public function testEtherpad(): void
    {
        $room = new Rooms();
        $room->setUidReal('test123')
            ->setUid('test5432');

        $applicationUrlGen = self::getContainer()->get(ApplicationUrlGenerator::class);
        self::assertEquals('http://etherpadurl.com/p/test123?showChat=false&userName=%name%', $applicationUrlGen->createEtherpadLink($room));
        $user = new User();
        $user->setFirstName('Vorname')
            ->setLastName('nachname')
            ->setUsername('username');
        self::assertEquals('http://etherpadurl.com/p/test123?showChat=false&userName=nachname%2C+Vorname', $applicationUrlGen->createEtherpadLink($room, $user));
        $lobbyUser = new LobbyWaitungUser();
        $lobbyUser->setShowName('test user name');
        self::assertEquals('http://etherpadurl.com/p/test123?showChat=false&userName=test+user+name', $applicationUrlGen->createEtherpadLink($room, $lobbyUser));
        $repeater = new Repeat();
        $repeater->setUid('testrepeater');
        $room->setRepeater($repeater);
        self::assertEquals('http://etherpadurl.com/p/testrepeater?showChat=false&userName=test+user+name', $applicationUrlGen->createEtherpadLink($room, $lobbyUser));
    }

    public function testWhitebophir(): void
    {
        $room = new Rooms();
        $room->setUidReal('test123')
            ->setUid('test5432');

        $applicationUrlGen = self::getContainer()->get(ApplicationUrlGenerator::class);
        $tokenService = self::getContainer()->get(WhiteboardJwtService::class);
        $token = $tokenService->createJwt($room);
        self::assertEquals('http://whiteboardurl.com/boards/test123?token=' . $token, $applicationUrlGen->createWhitebophirLink($room));
        $token = $tokenService->createJwt($room, true);
        self::assertEquals('http://whiteboardurl.com/boards/test123?token=' . $token, $applicationUrlGen->createWhitebophirLink($room, true));
        $user = new User();
        $user->setFirstName('Vorname')
            ->setLastName('nachname')
            ->setUsername('username');
        $repeater = new Repeat();
        $repeater->setUid('testrepeater');
        $room->setRepeater($repeater);
        $token = $tokenService->createJwt($room);
        self::assertEquals('http://whiteboardurl.com/boards/testrepeater?token=' . $token, $applicationUrlGen->createWhitebophirLink($room));
        $token = $tokenService->createJwt($room, true);
        self::assertEquals('http://whiteboardurl.com/boards/testrepeater?token=' . $token, $applicationUrlGen->createWhitebophirLink($room, true));
        $repeater->setUid(null)
            ->setWeeks(2)
            ->setDays(2)
            ->setRepetation(10)
            ->setRepeatType(1)
            ->setStartDate(new \DateTime());
        $res = $applicationUrlGen->createWhitebophirLink($room, true);
        $repeater = $room->getRepeater();
        $token = $tokenService->createJwt($room, true);
        self::assertEquals('http://whiteboardurl.com/boards/' . $repeater->getUid() . '?token=' . $token, $res);
    }
}
