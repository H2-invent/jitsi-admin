<?php

namespace App\DataFixtures;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoomFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {

    // create a user
        $user = new \App\Entity\User();
        $user->setEmail('test@local.de');
        $user->setCreatedAt(new \DateTime());
        $user->setKeycloakId(123456);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRegisterId(123456);
        $user->setSpezialProperties(array('ou'=>'Test1','departmentNumber'=>'1234',));
        $user->setTimeZone('Europe/Berlin');
        $user->setUuid('lksdhflkjdsljflkjds');
        $user->setUsername('test@local.de');
        $user->setCreatedAt(new \DateTime());
        $manager->persist($user);

        //create a server
        $server = new Server();
        $server->setUrl('meet.jit.si');
        $server->setAdministrator($user);
        $server->addUser($user);
        $server->setSlug('test');
        $server->setJwtModeratorPosition(0);
        $manager->persist($server);
        $manager->flush();

        // create rooms
        for ($i = 0; $i < 20; $i++) {
            $room = new Rooms();
            $room->setTimeZone('Europe/Berlin');
            $room->setModerator($user);
            $room->setAgenda('Testagenda:'.$i);
            $room->setDuration(60);
            $room->setDissallowPrivateMessage(true);
            $room->setDissallowScreenshareGlobal(true);
            $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'));
            $end = clone $start;
            $end->modify('+60min');
            $room->setStart($start);
            $room->setStartUtc((clone $start)->setTimezone(new \DateTimeZone('UTC')));
            $room->setEnddate($end);
            $room->setEndDateUtc((clone $end)->setTimezone(new \DateTimeZone('UTC')));
            $room->addUser($user);
            $room->setUid(md5(uniqid()));
            $room->setUidReal(md5(uniqid()));
            $room->setSlug('test');
            $room->setScheduleMeeting(false);
            $room->setName('TestMeeting: '.$i);
            $room->setSequence(0);
            $room->setServer($server);
            $manager->persist($room);
        }
        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setModerator(null);
        $room->setAgenda('Testagenda:'.$i);
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $end = clone $start;
        $end->modify('+60min');
        $room->setStart($start);
        $room->setStartUtc((clone $start)->setTimezone(new \DateTimeZone('UTC')));
        $room->setEnddate($end);
        $room->setEndDateUtc((clone $end)->setTimezone(new \DateTimeZone('UTC')));
        $room->setUid(md5(uniqid()));
        $room->setUidReal(md5(uniqid()));
        $room->setSlug('test');
        $room->setScheduleMeeting(false);
        $room->setName('No Right');
        $room->setSequence(0);
        $room->setServer($server);
        $manager->persist($room);
        $manager->flush();
    }
}
