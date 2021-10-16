<?php

namespace App\DataFixtures;

use App\Entity\License;
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
        $user->setSpezialProperties(array('ou' => 'Test1', 'departmentNumber' => '1234',));
        $user->setTimeZone('Europe/Berlin');
        $user->setUuid('lksdhflkjdsljflkjds');
        $user->setUsername('test@local.de');
        $user->setCreatedAt(new \DateTime());
        $manager->persist($user);

        $user2 = new \App\Entity\User();
        $user2->setEmail('test@local2.de');
        $user2->setCreatedAt(new \DateTime());
        $user2->setKeycloakId(123456);
        $user2->setFirstName('Test');
        $user2->setLastName('User');
        $user2->setRegisterId(123456);
        $user2->setSpezialProperties(array('ou' => 'Test1', 'departmentNumber' => '1234',));
        $user2->setTimeZone('Europe/Berlin');
        $user2->setUuid('lksdhflkjdsljflkjds');
        $user2->setUsername('test2@local.de');
        $user2->setCreatedAt(new \DateTime());
        $manager->persist($user2);

        $user3 = new \App\Entity\User();
        $user3->setEmail('test@local3.de');
        $user3->setCreatedAt(new \DateTime());
        $user3->setRegisterId(123456);
        $user3->setCreatedAt(new \DateTime());
        $manager->persist($user3);

        $user4 = new \App\Entity\User();
        $user4->setEmail('test@local4.de');
        $user4->setCreatedAt(new \DateTime());
        $user4->setRegisterId(123456);
        $user4->setCreatedAt(new \DateTime());
        $manager->persist($user4);

        $user->addAddressbook($user2);
        $manager->persist($user);
        $manager->flush();

        //create a server
        $server = new Server();
        $server->setUrl('meet.jit.si');
        $server->setAdministrator($user);
        $server->addUser($user);
        $server->setSlug('test');
        $server->setLogoUrl('https://test.test');
        $server->setAppSecret('jitsiSecret');
        $server->setAppId('jitsiId');
        $server->setJwtModeratorPosition(0);
        $server->setPrivacyPolicy('https://privacy.dev');
        $server->setServerName('Server without License');
        $manager->persist($server);
        $manager->flush();
        $license = new License();
        $license->setUrl('meet.jit.si2');
        $license->setLicense('{"signature":"4b1e1205ad1a492f33646c2f499c11b0252335b16da64d60804f6e0a5ca55b4c811bbad8faf0c3aef41a51ee94b5f93d4d2f5852e35a557280f06794bccbd5ee4cdd8e894f5d474f36b6127f77198a0a28cf369c447963f45b41ada180de36e309ea18b060dadfae53599118443c849cd86b78907d05ef5f376075c6bb7063682fbd05df57d3ec74b72b14f89bf2dc3defa8a2181bb12f0b5feef1e8cc731606b0e6c28a9e0d39c46d04cad228ab825457d79f8ec4047f5b8476fba742e18778b1934076767cc0e6fb874e865d1ac6ae5034282a9952c6091cf9f0bf16739c72c52e7e2d00ecad797cc3cc30f841dc3d0c51134a2a5200a40cec93c76e32038beaf4210973f3c946da8aeb06cb9c09d6bbb3e9137a5d88f3bf38f9ecfdab02117edc054161d2345ccc9d15bd9c59e696998e9d102d77ca2548f872a44f3150f81b24a28e741ee85ae99b24fd1938d5bcf906016ccf09a4f20da468113181b3b4653b2eff5ffc628692dd720c62fd063f5baa13c1a9ab60e88cc462efa15612bbbed1780a9c46ce851f422c7e5dd861f1aa7304d3cb87331d12e67496b39703d3e62f8305381343ee54f6dde8718a83581a12edceedbc0543f1ae226c1ae4acdaaa2ed09191593164dd2635319c09da53803d26a5cf14a84fb35a73d8688fdad251e33ed4719ee9d4281247d0cb1adfa62b220257e396a061d5598a4a401551dc","entry":{"valid_until":"2024-07-04","server_url":"meet.jit.si2","license_key":"f5c627f7ac98bef45fcfdd5fcade0246"}}');
        $license->setValidUntil(new \DateTime('2024-07-04'));
        $license->setLicenseKey('f5c627f7ac98bef45fcfdd5fcade0246');
        $manager->persist($license);
        $manager->flush();
        $server = new Server();
        $server->setUrl('meet.jit.si2');
        $server->setAdministrator($user);
        $server->addUser($user);
        $server->setSlug('test2');
        $server->setLogoUrl('https://test.img');
        $server->setAppSecret('jitsiSecret');
        $server->setAppId('jitsiId');
        $server->setJwtModeratorPosition(0);
        $server->setLicenseKey('f5c627f7ac98bef45fcfdd5fcade0246');
        $server->setShowStaticBackgroundColor(false);
        $server->setServerName('Server with License');
        $manager->persist($server);
        $manager->flush();
        // create rooms
        for ($i = 0; $i < 20; $i++) {
            $room = new Rooms();
            $room->setTimeZone('Europe/Berlin');
            $room->setModerator($user);
            $room->setAgenda('Testagenda:' . $i);
            $room->setDuration(60);
            $room->setDissallowPrivateMessage(true);
            $room->setDissallowScreenshareGlobal(true);
            $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('+' . ($i * 2) . 'minutes');
            $end = clone $start;
            $end->modify('+60min');
            $room->setStart($start);
            $room->setEnddate($end);
            $room->addUser($user);
            $room->addUser($user2);
            $room->addUser($user3);
            $room->setUid('12345678'.$i);
            $room->setUidReal('987654321'.$i);
            $room->setSlug('test');
            $room->setScheduleMeeting(false);
            $room->setName('TestMeeting: ' . $i);
            $room->setSequence(0);
            $room->setServer($server);
            $manager->persist($room);
        }
        for ($i = 0; $i < 20; $i++) {
            $room = new Rooms();
            $room->setTimeZone('America/Adak');
            $room->setModerator($user);
            $room->setAgenda('Testagenda:' . $i);
            $room->setDuration(60);
            $room->setDissallowPrivateMessage(true);
            $room->setDissallowScreenshareGlobal(true);
            $start = (new \DateTime())->setTimezone(new \DateTimeZone('America/Adak'))->modify('+' . ($i * 2) . 'minutes');
            $end = clone $start;
            $end->modify('+60min');
            $room->setStart($start);
            $room->setEnddate($end);
            $room->addUser($user);
            $room->addUser($user2);
            $room->addUser($user3);
            $room->setUid('13579'.$i);
            $room->setUid('97531'.$i);
            $room->setSlug('test');
            $room->setScheduleMeeting(false);
            $room->setName('TestMeeting_Amerika: ' . $i);
            $room->setSequence(0);
            $room->setServer($server);
            $manager->persist($room);
        }

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setModerator(null);
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $end = clone $start;
        $end->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setUid(md5(uniqid()));
        $room->setUidReal(md5(uniqid()));
        $room->setSlug('test7');
        $room->setScheduleMeeting(false);
        $room->setName('No Right');
        $room->setSequence(0);
        $room->setServer($server);
        $manager->persist($room);
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime('tomorrow'))->setTimezone(new \DateTimeZone('Europe/Berlin'))->setTime(10, 0);
        $end = clone $start;
        $end->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->setUid(md5(uniqid()));
        $room->setUidReal(md5(uniqid()));
        $room->setSlug('test5');
        $room->setScheduleMeeting(false);
        $room->setName('Room Tomorrow');
        $room->setSequence(0);
        $room->setServer($server);
        $manager->persist($room);
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(0);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setStart(null);
        $room->setEnddate(null);
        $room->setModerator($user);
        $room->addUser($user);
        $room->setUid('561d6f51s6f');
        $room->setUidReal('5615ds1f65ds');
        $room->setSlug('test_open_room3');
        $room->setScheduleMeeting(false);
        $room->setName('This Room has no participants and fixed room');
        $room->setSequence(0);
        $room->setServer($server);
        $room->setTotalOpenRooms(true);
        $room->setPersistantRoom(true);
        $room->setTotalOpenRoomsOpenTime(1);
        $manager->persist($room);
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(0);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (null);
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->addUser($user3);
        $room->setUid('12313231sdf');
        $room->setUidReal('561984sdf');
        $room->setSlug('test_open_room2');
        $room->setScheduleMeeting(false);
        $room->setName('This is a fixed room');
        $room->setSequence(0);
        $room->setServer($server);
        $room->setTotalOpenRooms(false);
        $room->setPersistantRoom(true);
        $manager->persist($room);
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime('tomorrow'))->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $end = clone $start;
        $end->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->addUser($user);
        $room->setUid('lkdsjfkljvcxk');
        $room->setUidReal('5615sd6fs');
        $room->setSlug('test5');
        $room->setScheduleMeeting(false);
        $room->setName('Room with Start and no Participants list');
        $room->setSequence(0);
        $room->setTotalOpenRooms(true);
        $room->setServer($server);
        $manager->persist($room);
        $manager->flush();
    }
}
