<?php

namespace App\Tests\Join;

use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Service\RoomService;
use App\UtilsHelper;
use Firebase\JWT\JWT;
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
        $url =  $jwtService->createUrl('a',$room,true,null,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,true,null,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()). '%22',$url);

    }
    public function testJwtServerhasNoAppId(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $jwtService = $this->getContainer()->get(RoomService::class);
        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name' => 'TestMeeting: 0'));
        $server = $room->getServer();
        $server->setFeatureEnableByJWT(false);
        $server->setAppId(null);
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, true);
        $res = null;
        $this->assertEquals($res, $payload);
        $url =  $jwtService->createUrl('a',$room,true,null,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,true,null,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'#config.subject=%22' . UtilsHelper::slugify($room->getName()). '%22',$url);

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
        $url =  $jwtService->createUrl('a',$room,true,null,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,true,null,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
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
        $url =  $jwtService->createUrl('a',$room,false,null,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,false,null,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
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
        $url =  $jwtService->createUrl('a',$room,false,null,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,false,null,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' .UtilsHelper::slugify($room->getName()) . '%22',$url);

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
        $url =  $jwtService->createUrl('a',$room,true,null,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,true,null,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);

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
        $url =  $jwtService->createUrl('a',$room,true,null,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,true,null,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);

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
        $url =  $jwtService->createUrl('a',$room,false,$userRoom,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,false,$userRoom,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);

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
        $url =  $jwtService->createUrl('a',$room,false,$userRoom,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,false,$userRoom,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);

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
        $url =  $jwtService->createUrl('a',$room,false,$userRoom,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,false,$userRoom,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);

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
        $url =  $jwtService->createUrl('a',$room,false,$userRoom,'Test User');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,false,$userRoom,'Test User');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);

    }
    public function testJwtModeratorWithJwtOptionsNoAllowScreensharewithPrivateMessageandScreenShareMOderateinContextWithAvatar(): void
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
        $payload = $jwtService->genereateJwtPayload('Test User', $room, $server, false, $userRoom,'https://image.de');
        $res = array(
            'aud' => 'jitsi_admin',
            'iss' => $server->getAppId(),
            'sub' => $server->getUrl(),
            'room' => $room->getUid(),
            'context' => array(
                'user' => array(
                    'name' => 'Test User',
                    'moderator' => false,
                    'avatar'=> 'https://image.de'
                ),
                'features' => array(
                    'screen-sharing' => true,
                    'private-message' => true,
                )
            ),

        );
        $this->assertEquals($res, $payload);
        $url =  $jwtService->createUrl('a',$room,false,$userRoom,'Test User','https://image.de');
        $this->assertEquals('jitsi-meet://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);
        $url = $jwtService->createUrl('b',$room,false,$userRoom,'Test User','https://image.de');
        $this->assertEquals('https://'.$server->getUrl().'/'.$room->getUid().'?jwt='.JWT::encode($payload,$server->getAppSecret()).'#config.subject=%22' . UtilsHelper::slugify($room->getName()) . '%22',$url);

    }
}
