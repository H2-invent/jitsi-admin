<?php

namespace App\Tests;

use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JwtTest extends KernelTestCase
{
    public function testJwtModeratorNOJwtOptions(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(false);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, true);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
            ),
            'moderator' => true,
        );
        $this->assertEquals($res, $payload);

        //$routerService = self::$container->get('router');
        //$myCustomService = self::$container->get(CustomService::class);
    }

    public function testJwtModeratorWithJwtOptions(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, true);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => true,
                )
            ),
            'moderator' => true,
        );
        $this->assertEquals($res, $payload);
    }

    public function testJwtNoModeratorWithJwtOptions(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $room->setDissallowPrivateMessage(false);
        $room->setDissallowScreenshareGlobal(false);
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, false);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => true,
                )
            ),
            'moderator' => false,
        );
        $this->assertEquals($res, $payload);
    }

    public function testJwtNoModeratorWithJwtOptionsNoAllowScreenshare(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setDissallowPrivateMessage(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, false);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => false,
                    'private-message' => false,
                )
            ),
            'moderator' => false,
        );
        $this->assertEquals($res, $payload);
    }

    public function testJwtModeratorWithJwtOptionsNoAllowScreenshare(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setDissallowPrivateMessage(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, true);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => true,
                )
            ),
            'moderator' => true,
        );
        $this->assertEquals($res, $payload);
    }
    public function testJwtModeratorWithJwtOptionsNoAllowScreensharewithUserRoomMOderator(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setDissallowPrivateMessage(true);
        $userRoom = new RoomsUser();
        $userRoom->setRoom($room);
        $userRoom->setModerator(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, true, $userRoom);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => true,
                )
            ),
            'moderator' => true,
        );
        $this->assertEquals($res, $payload);
    }
    public function testJwtModeratorWithJwtOptionsNoAllowScreensharewithScreenShare(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setDissallowPrivateMessage(true);
        $userRoom = new RoomsUser();
        $userRoom->setRoom($room);
        $userRoom->setShareDisplay(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, false, $userRoom);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => false,
                )
            ),
            'moderator' => false,
        );
        $this->assertEquals($res, $payload);
    }
    public function testJwtModeratorWithJwtOptionsNoAllowScreensharewithPrivateMessage(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setDissallowPrivateMessage(true);
        $userRoom = new RoomsUser();
        $userRoom->setRoom($room);
        $userRoom->setPrivateMessage(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, false, $userRoom);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => false,
                    'private-message' => true,
                )
            ),
            'moderator' => false,
        );
        $this->assertEquals($res, $payload);
    }
    public function testJwtModeratorWithJwtOptionsNoAllowScreensharewithPrivateMessageandScreenShare(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setDissallowPrivateMessage(true);
        $userRoom = new RoomsUser();
        $userRoom->setRoom($room);
        $userRoom->setPrivateMessage(true);
        $userRoom->setShareDisplay(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, false, $userRoom);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => true,
                )
            ),
            'moderator' => false,
        );
        $this->assertEquals($res, $payload);
    }
    public function testJwtModeratorWithJwtOptionsNoAllowScreensharewithPrivateMessageandScreenShareMOderateinContext(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(true);
        $server->setJwtModeratorPosition(1);
        $room->setDissallowScreenshareGlobal(true);
        $room->setDissallowPrivateMessage(true);
        $userRoom = new RoomsUser();
        $userRoom->setRoom($room);
        $userRoom->setPrivateMessage(true);
        $userRoom->setShareDisplay(true);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, false, $userRoom);
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                    'moderator' => false,
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => true,
                )
            ),

        );
        $this->assertEquals($res, $payload);
    }
}
