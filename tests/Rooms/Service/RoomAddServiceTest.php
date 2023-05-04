<?php

namespace App\Tests\Rooms\Service;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\RoomAddService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomAddServiceTest extends KernelTestCase
{
    public function testaddParticipant(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = "test@local5.de" . PHP_EOL . PHP_EOL . "test@local4.de" . PHP_EOL . "test@local6.de";
        $res = $roomAddService->createParticipants($user, $room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de', $data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local4.de', $data->getEmail());
                    break;
                case 5:
                    self::assertEquals('test@local6.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(6, sizeof($room->getUser()->toArray()));
    }

    public function testaddParticipantEmpty(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = "test@local5.de\n    \ntest@local6.de";
        $res = $roomAddService->createParticipants($user, $room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de', $data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(5, sizeof($room->getUser()->toArray()));
    }

    public function testaddParticipantEmptyLine(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = "  \ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createParticipants($user, $room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local4.de', $data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(5, sizeof($room->getUser()->toArray()));
    }

    public function testaddParticipantWrongEmail(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = "test@local5\ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createParticipants($user, $room);
        self::assertEquals(1, sizeof($res));
        self::assertEquals('test@local5', $res[0]);
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local4.de', $data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(5, sizeof($room->getUser()->toArray()));
    }

    public function testaddModerator(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = "test@local5.de\ntest@local4.de\ntest@local6.de\nldapUser@local.de";
        $res = $roomAddService->createModerators($user, $room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de', $data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local4.de', $data->getEmail());
                    break;
                case 5:
                    self::assertEquals('test@local6.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(7, sizeof($room->getUser()->toArray()));
        self::assertEquals(4, sizeof($room->getUserAttributes()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local5.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[0]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[0]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[0]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[0]->getPrivateMessage());
        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[1]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[1]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[1]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[1]->getPrivateMessage());
        $user = $userRepo->findOneBy(['email' => 'test@local6.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[2]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[2]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[2]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[2]->getPrivateMessage());
        $user = $userRepo->findOneBy(['email' => 'ldapUser@local.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[3]->getUser());
        self::assertEquals(false, $room->getUserAttributes()[3]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[3]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[3]->getPrivateMessage());
    }

    public function testaddModeratorEmpty(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = "test@local5.de\n   \ntest@local6.de";
        $res = $roomAddService->createModerators($user, $room);
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local5.de', $data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(5, sizeof($room->getUser()->toArray()));
        self::assertEquals(2, sizeof($room->getUserAttributes()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local5.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[0]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[0]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[0]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[0]->getPrivateMessage());
        $user = $userRepo->findOneBy(['email' => 'test@local6.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[1]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[1]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[1]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[1]->getPrivateMessage());
    }

    public function testaddModeratorWrongEmail(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $user = "test@local5\ntest@local4.de\ntest@local6.de";
        $res = $roomAddService->createModerators($user, $room);
        self::assertEquals(1, sizeof($res));
        self::assertEquals('test@local5', $res[0]);
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
                case 3:
                    self::assertEquals('test@local4.de', $data->getEmail());
                    break;
                case 4:
                    self::assertEquals('test@local6.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(5, sizeof($room->getUser()->toArray()));
        self::assertEquals(2, sizeof($room->getUserAttributes()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local4.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[0]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[0]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[0]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[0]->getPrivateMessage());
        $user = $userRepo->findOneBy(['email' => 'test@local6.de']);
        self::assertEquals($user, $room->getUserAttributes()->toArray()[1]->getUser());
        self::assertEquals(true, $room->getUserAttributes()[1]->getModerator());
        self::assertEquals(false, $room->getUserAttributes()[1]->getShareDisplay());
        self::assertEquals(false, $room->getUserAttributes()[1]->getPrivateMessage());
    }

    public function testaddParticipantWhenParrticipantIsCreator(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $creator = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room->setCreator($creator);
        $user = "test@local2.de";
        $res = $roomAddService->createModerators($user, $room, $room->getCreator());
        self::assertEquals(1, sizeof($res));
        self::assertEquals('test@local2.de', $res[0]);
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
    }
    public function testaddParticipantWhenParrticipantIsCreatorinviterisModerator(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $creator = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room->setCreator($creator);
        $user = "test@local2.de";
        $res = $roomAddService->createModerators($user, $room, $room->getModerator());
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
    }


    public function testaddModeratorWhereParrticipantIsCreator(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $creator = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room->setCreator($creator);
        $user = "test@local2.de";
        $res = $roomAddService->createModerators($user, $room, $room->getCreator());
        self::assertEquals(1, sizeof($res));
        self::assertEquals('test@local2.de', $res[0]);
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
    }

    public function testaddModeratorWhereParrticipantIsCreatorbutModeratorAdds(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $creator = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $room->setCreator($creator);
        $user = "test@local2.de";
        $res = $roomAddService->createModerators($user, $room, $room->getModerator());
        self::assertEquals(0, sizeof($res));
        $count = 0;
        foreach ($room->getUser() as $data) {
            switch ($count) {
                case 0:
                    self::assertEquals('test@local.de', $data->getEmail());
                    break;
                case 1:
                    self::assertEquals('test@local2.de', $data->getEmail());
                    break;
                case 2:
                    self::assertEquals('test@local3.de', $data->getEmail());
                    break;
            }
            $count++;
        }
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
    }

    public function testremoveParticipant(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $roomAddService->removeUserFromRoom($user, $room);
        self::assertEquals(2, sizeof($room->getUser()->toArray()));
    }

    public function testremoveParticipantWithCallerIdAndRoomUSer(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        self::assertEquals(0, $room->getUserAttributes()->count());
        self::assertEquals(0, $room->getCallerIds()->count());
        $callerId = new CallerId();
        $callerId->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setCallerId('kjdshfsd');
        $manager->persist($callerId);
        $manager->flush();
        $user->addCallerId($callerId);
        $roomUser = new RoomsUser();
        $roomUser->setRoom($room)
            ->setUser($user)
            ->setModerator(true)
            ->setLobbyModerator(true);
        $manager->persist($roomUser);
        $manager->flush();
        $user->addRoomsAttributes($roomUser);
        $room->addUserAttribute($roomUser);
        $room->addCallerId($callerId);
        $manager->persist($room);
        $manager->persist($user);
        $manager->flush();
        $calloutSession = new CalloutSession();
        $calloutSession->setState(0)
            ->setUid('ksjdhkjfhdsf')
            ->setInvitedFrom($room->getModerator())
            ->setUser($user)
            ->setCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setLeftRetries(2);
        $user->addCalloutSession($calloutSession);
        $room->addCalloutSession($calloutSession);
        $manager->persist($calloutSession);
        $manager->flush();

        self::assertEquals(1, $room->getUserAttributes()->count());
        self::assertEquals(1, $room->getCallerIds()->count());
        self::assertEquals(1, $room->getCalloutSessions()->count());
        $roomAddService->removeUserFromRoom($user, $room);
        self::assertEquals(0, $room->getUserAttributes()->count());
        self::assertEquals(0, $room->getCallerIds()->count());
        self::assertEquals(0, $room->getCalloutSessions()->count());
        self::assertEquals(2, sizeof($room->getUser()->toArray()));
    }

    public function testremoveParticipantWithCallerIdAndRoomUSerAndRepeater(): void
    {
        $kernel = self::bootKernel();
        $roomAddService = self::getContainer()->get(RoomAddService::class);
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, sizeof($room->getUser()->toArray()));
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        self::assertEquals(0, $room->getUserAttributes()->count());
        self::assertEquals(0, $room->getCallerIds()->count());
        $callerId = new CallerId();
        $callerId->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setCallerId('kjdshfsd');
        $manager->persist($callerId);
        $manager->flush();
        $user->addCallerId($callerId);
        $roomUser = new RoomsUser();
        $roomUser->setRoom($room)
            ->setUser($user)
            ->setModerator(true)
            ->setLobbyModerator(true);
        $manager->persist($roomUser);
        $manager->flush();
        $user->addRoomsAttributes($roomUser);
        $room->addUserAttribute($roomUser);
        $room->addCallerId($callerId);
        $manager->persist($room);
        $manager->persist($user);
        $manager->flush();
        self::assertEquals(1, $room->getUserAttributes()->count());
        self::assertEquals(1, $room->getCallerIds()->count());
        $roomAddService->removeUserFromRoom($user, $room);
        self::assertEquals(0, $room->getUserAttributes()->count());
        self::assertEquals(0, $room->getCallerIds()->count());
        self::assertEquals(2, sizeof($room->getUser()->toArray()));
    }
}
