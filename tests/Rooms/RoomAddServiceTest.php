<?php

namespace App\Tests\Rooms;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\RoomAddService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomAddServiceTest extends KernelTestCase
{
    public function testaddParticipant(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = "test@local5.de\ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createParticipants($user,$room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data){
            switch ($count){
                case 0:
                    self::assertEquals('test@local.de',$data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de',$data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de',$data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de',$data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local4.de',$data->getEmail());
                    break;
                case 5:
                    self::assertEquals('test@local6.de',$data->getEmail());
                    break;

            }
            $count++;
        }
        self::assertEquals(6,sizeof($room->getUser()->toArray()));
    }
    public function testaddParticipantEmpty(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = "test@local5.de\n    \ntest@local6.de";
        $res = $roomAddService->createParticipants($user,$room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data){
            switch ($count){
                case 0:
                    self::assertEquals('test@local.de',$data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de',$data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de',$data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de',$data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de',$data->getEmail());
                    break;

            }
            $count++;
        }
        self::assertEquals(5,sizeof($room->getUser()->toArray()));
    }
    public function testaddParticipantEmptyLine(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = "  \ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createParticipants($user,$room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data){
            switch ($count){
                case 0:
                    self::assertEquals('test@local.de',$data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de',$data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de',$data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local4.de',$data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de',$data->getEmail());
                    break;

            }
            $count++;
        }
        self::assertEquals(5,sizeof($room->getUser()->toArray()));
    }
    public function testaddParticipantWrongEmail(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = "test@local5\ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createParticipants($user,$room);
        self::assertEquals(1, sizeof($res));
        self::assertEquals('test@local5', $res[0]);
        $count = 0;
        foreach ($room->getUser() as $data){
            switch ($count){
                case 0:
                    self::assertEquals('test@local.de',$data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de',$data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de',$data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local4.de',$data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de',$data->getEmail());
                    break;


            }
            $count++;
        }
        self::assertEquals(5,sizeof($room->getUser()->toArray()));
    }

    public function testaddModerator(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = "test@local5.de\ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createModerators($user,$room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data){
            switch ($count){
                case 0:
                    self::assertEquals('test@local.de',$data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de',$data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de',$data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de',$data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local4.de',$data->getEmail());
                    break;
                case 5:
                    self::assertEquals('test@local6.de',$data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(6,sizeof($room->getUser()->toArray()));
        self::assertEquals(3,sizeof($room->getUserAttributes()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local5.de'));
        self::assertEquals($user,$room->getUserAttributes()->toArray()[0]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[0]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[0]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[0]->getPrivateMessage());
        $user = $userRepo->findOneBy(array('email'=>'test@local4.de'));
        self::assertEquals($user,$room->getUserAttributes()->toArray()[1]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[1]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[1]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[1]->getPrivateMessage());
        $user = $userRepo->findOneBy(array('email'=>'test@local6.de'));
        self::assertEquals($user,$room->getUserAttributes()->toArray()[2]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[2]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[2]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[2]->getPrivateMessage());
    }

    public function testaddModeratorEmpty(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = "test@local5.de\n   \ntest@local6.de";
        $res = $roomAddService->createModerators($user,$room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data){
            switch ($count){
                case 0:
                    self::assertEquals('test@local.de',$data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de',$data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de',$data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de',$data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de',$data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(5,sizeof($room->getUser()->toArray()));
        self::assertEquals(2,sizeof($room->getUserAttributes()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local5.de'));
        self::assertEquals($user,$room->getUserAttributes()->toArray()[0]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[0]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[0]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[0]->getPrivateMessage());
        $user = $userRepo->findOneBy(array('email'=>'test@local6.de'));
        self::assertEquals($user,$room->getUserAttributes()->toArray()[1]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[1]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[1]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[1]->getPrivateMessage());
    }

    public function testaddModeratorWrongEmail(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        $user = "test@local5\ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createModerators($user,$room);
        self::assertEquals(1, sizeof($res));
        self::assertEquals('test@local5', $res[0]);
        $count = 0;
        foreach ($room->getUser() as $data){
            switch ($count){
                case 0:
                    self::assertEquals('test@local.de',$data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de',$data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de',$data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local4.de',$data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de',$data->getEmail());
                    break;


            }
            $count++;
        }
        self::assertEquals(5,sizeof($room->getUser()->toArray()));
        self::assertEquals(2,sizeof($room->getUserAttributes()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local4.de'));
        self::assertEquals($user,$room->getUserAttributes()->toArray()[0]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[0]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[0]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[0]->getPrivateMessage());
        $user = $userRepo->findOneBy(array('email'=>'test@local6.de'));
        self::assertEquals($user,$room->getUserAttributes()->toArray()[1]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[1]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[1]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[1]->getPrivateMessage());
    }
    public function testremodeParticipant(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('name'=>'TestMeeting: 0'));
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email'=>'test@local2.de'));
        $roomAddService->removeUserFromRoom($user, $room);
        self::assertEquals(2, sizeof($room->getUser()->toArray()));
    }
}
