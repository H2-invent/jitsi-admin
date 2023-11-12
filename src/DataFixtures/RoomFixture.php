<?php

namespace App\DataFixtures;

use App\Entity\AddressGroup;
use App\Entity\CallerRoom;
use App\Entity\Deputy;
use App\Entity\LdapUserProperties;
use App\Entity\License;
use App\Entity\LobbyWaitungUser;
use App\Entity\PredefinedLobbyMessages;
use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\Server;
use App\Entity\Tag;
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
        $user->setKeycloakId('123456');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRegisterId(123456);
        $user->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234', 'telephoneNumber' => '0123456789']);
        $user->setTimeZone('Europe/Berlin');
        $user->setUuid('lksdhflkjdsljflkjds');
        $user->setUid('kljlsdkjflkjdslfjsjkldlkjsdflkj');
        $user->setUsername('test@local.de');
        $user->setCreatedAt(new \DateTime());
        $user->setIndexer('test@local.de test@local.de test user test1 1234 0123456789');
        $manager->persist($user);


        $user2 = new \App\Entity\User();
        $user2->setEmail('test@local2.de');
        $user2->setCreatedAt(new \DateTime());
        $user2->setKeycloakId(123456);
        $user2->setFirstName('Test2');
        $user2->setLastName('User2');
        $user2->setRegisterId(123456);
        $user2->setSpezialProperties(['ou' => 'Test2', 'departmentNumber' => '1234', 'telephoneNumber' => '9876543210',]);
        $user2->setTimeZone('Europe/Berlin');
        $user2->setUuid('lksdhflkjdsljflhjkkjds');
        $user2->setUid('kljlsdkjflkjddfgslfjsdlkjsdflkj');
        $user2->setUsername('test2@local.de');
        $user2->setCreatedAt(new \DateTime());
        $user2->setIndexer('test@local2.de test@local2.de test2 user2 test2 1234 9876543210');
        $manager->persist($user2);


        $userLDAP = new \App\Entity\User();
        $userLDAP->setEmail('ldapUser@local.de');
        $userLDAP->setCreatedAt(new \DateTime());
        $userLDAP->setKeycloakId(123456);
        $userLDAP->setFirstName('LdapUSer');
        $userLDAP->setLastName('Ldap');
        $userLDAP->setRegisterId(123456);
        $userLDAP->setSpezialProperties(['ou' => 'AA', 'departmentNumber' => '45689', 'telephoneNumber' => '987654321012',]);
        $userLDAP->setTimeZone('Europe/Berlin');
        $userLDAP->setUuid('dfsdffscxv');
        $userLDAP->setUid('kljlsdkjflkjxcvvxcxcvddfgslfjsdlkjsdflkj');
        $userLDAP->setUsername('ldapUser@local.de');
        $userLDAP->setCreatedAt(new \DateTime());
        $userLDAP->setIndexer('ldapuser@local.de ldapuser@local.de ldapuser ldap aa 45689 987654321012');
        $manager->persist($userLDAP);
        $ldapUserProperty = new LdapUserProperties();
        $ldapUserProperty->setUser($userLDAP);
        $ldapUserProperty->setLdapDn('');
        $ldapUserProperty->setLdapHost('');
        $ldapUserProperty->setLdapNumber('ldap_3');
        $ldapUserProperty->setRdn('');
        $manager->persist($ldapUserProperty);


        $user3 = new \App\Entity\User();
        $user3->setEmail('test@local3.de');
        $user3->setUsername('test@local3.de');
        $user3->setCreatedAt(new \DateTime());
        $user3->setRegisterId(123456);
        $user3->setCreatedAt(new \DateTime());
        $user3->setUid('kjsdfhkjds');
        $user3->setIndexer('test@local3.de test@local3.de');
        $manager->persist($user3);

        $user4 = new \App\Entity\User();
        $user4->setEmail('test@local4.de');
        $user4->setUsername('test@local4.de');
        $user4->setCreatedAt(new \DateTime());
        $user4->setRegisterId(123456);
        $user4->setCreatedAt(new \DateTime());
        $user4->setUid('bjhxbcvuzcbxv7');
        $user4->setIndexer('test@local4.de test@local4.de');
        $manager->persist($user4);

        // create a user
        $user5 = new \App\Entity\User();
        $user5->setEmail('test@australia.de');
        $user5->setCreatedAt(new \DateTime());
        $user5->setKeycloakId('123456');
        $user5->setFirstName('Test');
        $user5->setLastName('User');
        $user5->setRegisterId(123456);
        $user5->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234', 'telephoneNumber' => '0123456789']);
        $user5->setTimeZone('Australia/Lindeman');
        $user5->setUuid('lksdhflkjdsljflkjds');
        $user5->setUid('kljlsdkjflkjdslfjsjkldlkjsdflkj');
        $user5->setUsername('test@australia.de');
        $user5->setCreatedAt(new \DateTime());
        $user5->setIndexer('test@australia.de test@australia.de test user test1 1234 0123456789');
        $manager->persist($user5);

        $user6 = new \App\Entity\User();
        $user6->setEmail('test@noTimeZone.de');
        $user6->setCreatedAt(new \DateTime());
        $user6->setKeycloakId('123456');
        $user6->setFirstName('Test');
        $user6->setLastName('User');
        $user6->setRegisterId(123456);
        $user6->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234', 'telephoneNumber' => '0123456789']);
        $user6->setUuid('lksdhflkjdsljflkjds');
        $user6->setUid('kljlsdkjflkjdslfjsjkldlkjsdflkj');
        $user6->setUsername('test@noTimeZone.de');
        $user6->setCreatedAt(new \DateTime());
        $user6->setIndexer('test@noTimeZone.de test@noTimeZone.de test user test1 1234 0123456789');
        $manager->persist($user6);

        $user->addAddressbook($user2);
        $user->addAddressbook($user3);

        $deputy1 = new Deputy();
        $deputy1->setManager($user5)
            ->setDeputy($user6)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(true);
        $deputy2 = new Deputy();
        $deputy2->setManager($user5)
            ->setDeputy($user6)
            ->setCreatedAt(new \DateTime())
            ->setIsFromLdap(true);


        $manager->persist($deputy1);
        $manager->persist($deputy2);
        $manager->flush();

        $group = new AddressGroup();
        $group->setLeader($user);
        $group->setIndexer('testgruppe');
        $group->setCreatedAt(new \DateTimeImmutable());
        $group->addMember($user2);
        $group->addMember($user3);
        $group->setName('Testgruppe');
        $manager->persist($group);
        $manager->persist($user);
        $manager->flush();

        //create a server
        $serverOriginal = new Server();
        $serverOriginal->setUrl('meet.jit.si');
        $serverOriginal->setAdministrator($user);
        $serverOriginal->addUser($user);
        $serverOriginal->setSlug('test');
        $serverOriginal->setLogoUrl('https://test.test');
        $serverOriginal->setAppSecret('jitsiSecret');
        $serverOriginal->setAppId('jitsiId');
        $serverOriginal->setJwtModeratorPosition(0);
        $serverOriginal->setPrivacyPolicy('https://privacy.dev');
        $serverOriginal->setServerName('Server without License');

        $manager->persist($serverOriginal);
        $manager->flush();

        $server = new Server();
        $server->setUrl('meet.jit.si3');
        $server->setAdministrator($user);
        $server->addUser($user);
        $server->setSlug('test');
        $server->setLogoUrl('https://test.test');
        $server->setAppSecret('');
        $server->setAppId('');
        $server->setJwtModeratorPosition(0);
        $server->setPrivacyPolicy('https://privacy.dev');
        $server->setServerName('Server no JWT');

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
        $server->setApiKey('TestApi');


        $server->setShowStaticBackgroundColor(false);
        $server->setServerName('Server with License');
        $manager->persist($server);
        $manager->flush();
        // create rooms
        for ($i = 0; $i < 20; $i++) {
            $room = new Rooms();
            $room->setTimeZone('Europe/Berlin');
            $room->setModerator($user);
            $room->setCreator($user);
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
            $room->setUid('12345678' . $i);
            $room->setUidReal('987654321' . $i);
            $room->setSlug('test');
            $room->setScheduleMeeting(false);
            $room->setName('TestMeeting: ' . $i);
            $room->setSequence(0);
            $room->setServer($server);
            $callerRoom = new CallerRoom();
            $callerRoom->setCallerId('1234' . $i);
            $callerRoom->setCreatedAt(new \DateTime());
            $room->setCallerRoom($callerRoom);
            $room->setHostUrl('http://localhost:8000');
            $manager->persist($room);
        }
        $start = new \DateTime('2021-01-01T15:00');
        for ($i = 0; $i < 20; $i++) {
            $room = new Rooms();
            $room->setTimeZone('Europe/Berlin');
            $room->setModerator($user);
            $room->setCreator($user);
            $room->setAgenda('Testagenda:' . $i);
            $room->setDuration(60);
            $room->setDissallowPrivateMessage(true);
            $room->setDissallowScreenshareGlobal(true);
            $room->setStart($start);
            $room->setEnddate($end);
            $room->addUser($user);
            $room->addUser($user2);
            $room->addUser($user3);
            $room->setUid('123456789' . $i);
            $room->setUidReal('987654321' . $i);
            $room->setSlug('test');
            $room->setScheduleMeeting(true);
            $room->setName('Termin finden: ' . $i);
            $room->setSequence(0);
            $room->setServer($server);
            $selectDate = new Scheduling();
            $selectDate->setUid(md5(uniqid()));
            $selectDate->setDescription('test');
            $selectDate->setRoom($room);
            for ($c = 0; $c < 5; $c++) {
                $time = new SchedulingTime();
                $time->setTime(clone $start);
                $start->modify('+1day');
                $selectDate->addSchedulingTime($time);
                $manager->persist($time);
            }

            $manager->persist($selectDate);
            $manager->persist($room);
        }
        for ($i = 0; $i < 20; $i++) {
            $room = new Rooms();
            $room->setTimeZone('America/Adak');
            $room->setModerator($user);
            $room->setCreator($user);
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
            $room->setUid('13579' . $i);
            $room->setUid('97531' . $i);
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
        $room->setCreator($user);
        $callerRoom = new CallerRoom();
        $callerRoom->setCallerId('1234noRight');
        $callerRoom->setCreatedAt(new \DateTime());
        $room->setCallerRoom($callerRoom);
        $room->setCallerRoom($callerRoom);
        $room->setAgenda('Testagenda:');
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
        $room->setCreator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->setUid('roomTomorrow');
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
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime('yesterday'))->setTimezone(new \DateTimeZone('Europe/Berlin'))->setTime(10, 0);
        $end = clone $start;
        $end->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->setCreator($user);
        $room->addUser($user2);
        $room->setUid(md5(uniqid()));
        $room->setUidReal(md5(uniqid()));
        $room->setSlug('test5');
        $room->setScheduleMeeting(false);
        $room->setName('Room Yesterday');
        $room->setSequence(0);
        $room->setServer($server);
        $callerRoom = new CallerRoom();
        $callerRoom->setCallerId('123456');
        $callerRoom->setCreatedAt(new \DateTime());
        $room->setCallerRoom($callerRoom);
        $manager->persist($room);
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('-10min');
        $end = clone $start;
        $end->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->setUid('runningRoomNow');
        $room->setUidReal(md5(uniqid()));
        $room->setSlug('test5');
        $room->setScheduleMeeting(false);
        $room->setName('Running Room');
        $room->setSequence(0);
        $room->setServer($server);
        $manager->persist($room);
        $manager->flush();


        //here we create some rommstatuses

        $roomStatus = new RoomStatus();
        $roomStatus->setCreated(true)
            ->setRoomCreatedAt(new \DateTime())
            ->setRoom($room)
            ->setJitsiRoomId('test@test.de')
            ->setUpdatedAt(new \DateTime())
            ->setCreatedAt(new \DateTime());
        $manager->persist($roomStatus);
        $manager->flush();

        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt(new \DateTime())
            ->setInRoom(true)
            ->setParticipantId('inderKonferenz@test.de')
            ->setParticipantName('in der Konferenz')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTime());
        $manager->persist($roomStatusPart);
        $manager->flush();
        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt(new \DateTime())
            ->setInRoom(false)
            ->setParticipantId('inderKonferenz3@test.de')
            ->setParticipantName('aus der Konferenz 1 Stunde')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTime())
            ->setLeftRoomAt((new \DateTime())->modify('+1hour'));
        $manager->persist($roomStatusPart);
        $manager->flush();

        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt(new \DateTime())
            ->setInRoom(false)
            ->setParticipantId('inderKonferenz3@test.de')
            ->setParticipantName('aus der Konferenz 1 Tag')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTime())
            ->setLeftRoomAt((new \DateTime())->modify('+1day'));
        $manager->persist($roomStatusPart);
        $manager->flush();


        $roomStatus = new RoomStatus();
        $roomStatus->setCreated(true)
            ->setRoomCreatedAt((new \DateTime())->modify('-2hours'))
            ->setRoom($room)
            ->setJitsiRoomId('test@test.de')
            ->setUpdatedAt(new \DateTime())
            ->setCreatedAt(new \DateTime())
            ->setDestroyed(true)
            ->setDestroyedAt((new \DateTime())->modify('-1hour'));
        $manager->persist($roomStatus);
        $manager->flush();

        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt((new \DateTime())->modify('-2hours'))
            ->setInRoom(false)
            ->setLeftRoomAt((new \DateTime())->modify('-1hour'))
            ->setParticipantId('inderKonferenz@test.de')
            ->setParticipantName('beim letzen mal')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTime())
            ->setDominantSpeakerTime(11100000);
        $manager->persist($roomStatusPart);
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
        $room->setCreator($user);
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
        $room->setStart(null);
        $room->setEnddate(null);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->setUid('561d6rtzf51s6fwer');
        $room->setUidReal('5615dfggfdds1f65ds');
        $room->setSlug('test_open_room3');
        $room->setScheduleMeeting(false);
        $room->setName('This Room has no participants and fixed room and Lobby activated');
        $room->setSequence(0);
        $room->setServer($server);
        $room->setTotalOpenRooms(true);
        $room->setPersistantRoom(true);
        $room->setLobby(true);
        $room->setTotalOpenRoomsOpenTime(1);
        $manager->persist($room);
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(0);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $room->setModerator($user);
        $room->setCreator($user);
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
        $room->setCreator($user);
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

        $room1 = new Rooms();
        $room1->setTimeZone('Europe/Berlin');
        $room1->setAgenda('Testagenda:' . $i);
        $room1->setDuration(60);
        $room1->setDissallowPrivateMessage(true);
        $room1->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime('tomorrow'))->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $end = clone $start;
        $end->modify('+60min');
        $room1->setStart($start);
        $room1->setEnddate($end);
        $room1->setModerator($user);
        $room1->setCreator($user);
        $room1->addUser($user);
        $room1->setUid('wertzzrtrrew');
        $room1->setUidReal('sdfgfhhjtr980joifjhg');
        $room1->setSlug('test534');
        $room1->setScheduleMeeting(false);
        $room1->setName('Room with Start and no Participants list and Lobby Activated');
        $room1->setSequence(0);
        $room1->setTotalOpenRooms(true);
        $room1->setLobby(true);
        $room1->setServer($server);
        $manager->persist($room1);
        $manager->flush();
        $lobbyTime = new \DateTime();
        for ($i = 0; $i < 10; $i++) {
            $lobbyUser = new LobbyWaitungUser();
            $lobbyUser->setWebsocketReady(true);
            $lobbyUser->setUser($user);
            $lobbyUser->setRoom($room1);
            $lobbyUser->setUid(md5($i));
            $lobbyUser->setCreatedAt(clone($lobbyTime->modify('-1 hour')));
            $lobbyUser->setShowName('LobbyUser ' . $i);
            $lobbyUser->setType('a');
            $manager->persist($lobbyUser);
        }
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('-10min');
        $end = clone $start;
        $end->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->addUser($user3);
        $room->setUid('12313231ghjgfdsdf');
        $room->setUidReal('561ghj984ssdfdf');
        $room->setSlug('lobby_room');
        $room->setScheduleMeeting(false);
        $room->setName('This is a room with Lobby');
        $room->setSequence(0);
        $room->setServer($server);
        $room->setLobby(true);
        $manager->persist($room);
        $manager->flush();

        $callerIdLoby = new CallerRoom();
        $callerIdLoby->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setCallerId('12341232');
        $manager->persist($callerIdLoby);
        $manager->flush();
        $tag = new Tag();
        $tag->setTitle('Test Tag Enabled');
        $tag->setPriority(-10);
        $tag->setDisabled(false);
        $manager->persist($tag);
        $manager->flush();
        $serverOriginal->addTag($tag);
        $manager->persist($serverOriginal);

        $tag = new Tag();
        $tag->setTitle('Test Tag Enabled No2');
        $tag->setPriority(-5);
        $tag->setDisabled(false);
        $manager->persist($tag);
        $manager->flush();
        $serverOriginal->addTag($tag);
        $manager->persist($serverOriginal);

        $tag = new Tag();
        $tag->setTitle('Test Tag Disabled');
        $tag->setPriority(-10);
        $tag->setDisabled(true);
        $serverOriginal->addTag($tag);
        $manager->persist($tag);
        $manager->persist($serverOriginal);
        $manager->flush();

        for ($i = 0; $i < 5; $i++) {
            $tag2 = new Tag();
            $tag2->setTitle('Test Tag ' . $i);
            $tag2->setPriority(10 * $i);
            $manager->persist($tag2);
            $serverOriginal->addTag($tag2);
            $manager->persist($serverOriginal);
        }
        $manager->flush();

        $predefined1 = new PredefinedLobbyMessages();
        $predefined1->setActive(true)
            ->setText('Bitte warten!')
            ->setCreatedAt(new \DateTime())
            ->setPriority(0);
        $manager->persist($predefined1);
        $predefined2 = new PredefinedLobbyMessages();
        $predefined2->setActive(false)
            ->setText('Bitte warten/Disabled!')
            ->setCreatedAt(new \DateTime())
            ->setPriority(1);
        $manager->persist($predefined2);
        $predefined3 = new PredefinedLobbyMessages();
        $predefined3->setActive(true)
            ->setText('Wir haben andere Themen!')
            ->setCreatedAt(new \DateTime())
            ->setPriority(2);

        $manager->persist($predefined3);
        $manager->flush();
    }
}
