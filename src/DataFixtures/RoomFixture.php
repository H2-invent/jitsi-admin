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
use App\Helper\UidGeneratorHelper;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoomFixture extends Fixture
{
    /**
     * Stable per-index UUIDs for the reminder rooms (TestMeeting: i) — deterministic
     * fixture data that other tests reference by value.
     *
     * @var array<int, string>
     */
    private const array REMINDER_UIDS = [
        'bed6ca41-512f-409f-95c1-4ad7e9b73018',
        '50902e5e-93a6-4ee1-817d-de4871b52d31',
        '02bbd5ca-e7ee-4f4d-8cac-76b803b4bab8',
        '1191c920-96ee-49bd-bc12-a8f6aa2a664b',
        'eb006faf-e0d0-4b64-ae5c-ca7329fb7830',
        '4ac48f8d-d20a-40a5-8a8a-b8fb218e7e7e',
        'c071e3ca-8e3a-4b42-a8b5-5fcf5ce7c5d0',
        '91fbee99-579e-4e17-9892-0011a7acbf46',
        'd4914751-9b3f-46d4-a7e2-d8eb0e77d159',
        '6e04846f-abaf-4e34-949a-9e9b614a5c43',
        '774c4b89-131c-4a51-9285-2d058f91ec01',
        'c5af0a70-b93e-488e-86e0-5bf1c0e5c01b',
        'b205e5e2-9695-4030-96d5-af2adbf9d01c',
        '1b0da9dc-c094-4b02-918e-9faf8669f1f7',
        '064f1054-0dc1-4ff9-afef-076c216d6cd8',
        '45545e43-b1c0-40b6-8b70-9f2c810c3ec1',
        '50230496-3a92-4d39-b3dd-bed471aaaf87',
        'f355ec2f-e4a0-40e5-aabf-a0869ec47ec3',
        'c9cd7ea0-577b-4115-884f-c53be296a2ad',
        '3e04d11b-6dfc-4ec1-975d-a66850856644',
    ];

    /**
     * Stable per-index UUIDs for the reminder rooms' uid_real values.
     *
     * @var array<int, string>
     */
    private const array REMINDER_UIDREALS = [
        'ecab6ec4-84dc-40c4-b97f-e364b3d23f3b',
        '6aa97048-fabb-4f75-865a-3b9be0af4d03',
        '0a75bbe7-fdeb-4406-a8e5-d15e9becd0ba',
        'b23a3619-fc4c-475b-90eb-79d86029538b',
        'fab456ec-460f-4bdd-b9ac-9240c09bb014',
        '598e835f-4eea-4f5c-b520-c101efd38463',
        'e8685926-be4e-48a4-ba96-79709226e155',
        'bcc66819-67d2-4202-9d33-8bea0690fa3c',
        '845e8bc6-fdff-4447-8632-2bec5fe22e1e',
        '487d5172-e132-476f-a188-3e6ed97545c9',
        '8771c307-df6d-47a6-86d5-15c2f7b5d29e',
        '872ac175-3a6f-4940-a2e9-01fd1165901e',
        '2ea5e507-67b5-403e-8d59-4d05f2a9e245',
        '0f660474-80be-44a6-9d88-bab7140b6395',
        'de987b92-6bd2-45d5-ab3e-d8673382c31d',
        '6d163a3d-2538-42be-ae96-bd4b9be250c3',
        '11cd3bf2-f9f6-4f3a-aa23-b50062a65d35',
        'ce991452-90cf-484e-9cf0-678ba82d0292',
        '33da3f45-364a-48bc-a33f-17ac60c4181f',
        'ad9b55a7-7578-4143-9482-6b5ea32e913e',
    ];

    /**
     * Stable per-index UUIDs for the lobby waiting users.
     *
     * @var array<int, string>
     */
    private const array LOBBY_UIDS = [
        'b61e49bf-c885-493c-8f26-89419f9c8b0b',
        '9e1d7a68-2bfd-4923-8f54-3fb7dbd87142',
        'a18a4c08-0e10-412c-af5a-b87ef0adf1a3',
        '88671974-70df-4f93-9bf7-6d46960e4c95',
        'b7704a6f-d2a4-4cfb-826c-6cbe44c5d078',
        'cc175eab-cab7-4e4c-ab03-af7a1cc014b6',
        '3f825a0a-2bf6-4c20-a51f-a1c9796a7c5f',
        'eed21817-3e11-4710-8136-9a803b1e271f',
        '83a04527-ba47-4530-a14b-fec9cfc31602',
        '96c5ce99-f175-4e97-b97d-326971af6747',
    ];

    public function load(ObjectManager $manager): void
    {
        // create a user
        $user = new \App\Entity\User();
        $user->setEmail('test@local.de');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setKeycloakId('5a041ecb-02e0-47f8-9ae3-29ecbb5c20ab');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRegisterId('542ae603-835a-41d1-93be-774ab255642e');
        $user->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234', 'telephoneNumber' => '0123456789']);
        $user->setTimeZone('Europe/Berlin');
        $user->setUuid('3910f10e-23b2-4381-9343-72fffa0187b2');
        $user->setUid('6bb9f8b7-05e8-4b1d-95d3-787eb2fa2ffb');
        $user->setUsername('test@local.de');
        $user->setCreatedAt(new \DateTimeImmutable());
        $user->setIndexer('test@local.de test@local.de test user 1234 test1 0123456789');
        $manager->persist($user);


        $user2 = new \App\Entity\User();
        $user2->setEmail('test@local2.de');
        $user2->setCreatedAt(new \DateTimeImmutable());
        $user2->setKeycloakId('f8b2e8d9-aa79-4d3b-ac46-739b930048c5');
        $user2->setFirstName('Test2');
        $user2->setLastName('User2');
        $user2->setRegisterId('6ee34e16-5fe5-431a-98b0-33bc5aeeb3b7');
        $user2->setSpezialProperties(['ou' => 'Test2', 'departmentNumber' => '1234', 'telephoneNumber' => '9876543210',]);
        $user2->setTimeZone('Europe/Berlin');
        $user2->setUuid('6d085d6f-e7bc-40d4-ab3e-eed89fca2703');
        $user2->setUid('a0591bf8-d69a-4039-97d2-e9fd69077785');
        $user2->setUsername('test2@local.de');
        $user2->setCreatedAt(new \DateTimeImmutable());
        $user2->setIndexer('test@local2.de test@local2.de test2 user2 1234 test2 9876543210');
        $manager->persist($user2);


        $userLDAP = new \App\Entity\User();
        $userLDAP->setEmail('ldapUser@local.de');
        $userLDAP->setCreatedAt(new \DateTimeImmutable());
        $userLDAP->setKeycloakId('abc87dc1-4835-4e39-ab23-04189ab77742');
        $userLDAP->setFirstName('LdapUSer');
        $userLDAP->setLastName('Ldap');
        $userLDAP->setRegisterId('bd4455ef-78c2-42e3-92ef-2a93114dede4');
        $userLDAP->setSpezialProperties(['ou' => 'AA', 'departmentNumber' => '45689', 'telephoneNumber' => '987654321012',]);
        $userLDAP->setTimeZone('Europe/Berlin');
        $userLDAP->setUuid('39c2fbd3-ee6d-4ee8-b907-27dfdda225ff');
        $userLDAP->setUid('d9c56bda-cd43-4dd6-b8fe-24b444a04f9f');
        $userLDAP->setUsername('ldapUser@local.de');
        $userLDAP->setCreatedAt(new \DateTimeImmutable());
        $userLDAP->setIndexer('ldapuser@local.de ldapuser@local.de ldapuser ldap 45689 aa 987654321012');
        $manager->persist($userLDAP);
        $ldapUserProperty = new LdapUserProperties();
        $ldapUserProperty->setUser($userLDAP);
        $ldapUserProperty->setLdapDn('cn=ldapUser@local.de,dc=example,dc=com');
        $ldapUserProperty->setLdapHost('');
        $ldapUserProperty->setLdapNumber('ldap_3');
        $ldapUserProperty->setRdn('');
        $manager->persist($ldapUserProperty);


        $user3 = new \App\Entity\User();
        $user3->setEmail('test@local3.de');
        $user3->setUsername('test@local3.de');
        $user3->setCreatedAt(new \DateTimeImmutable());
        $user3->setRegisterId('6bd4bc96-39a4-4c7f-b7c6-de04711ddcfd');
        $user3->setCreatedAt(new \DateTimeImmutable());
        $user3->setUid('ee8d670c-bcf2-4b9b-b763-3dc088945366');
        $user3->setIndexer('test@local3.de test@local3.de');
        $manager->persist($user3);

        $user4 = new \App\Entity\User();
        $user4->setEmail('test@local4.de');
        $user4->setUsername('test@local4.de');
        $user4->setCreatedAt(new \DateTimeImmutable());
        $user4->setRegisterId('2d11c143-c3a3-412a-a037-b6ed69e90479');
        $user4->setCreatedAt(new \DateTimeImmutable());
        $user4->setUid('9cb8cf62-86e2-417b-bd24-117100fb0f42');
        $user4->setIndexer('test@local4.de test@local4.de');
        $manager->persist($user4);

        // create a user
        $user5 = new \App\Entity\User();
        $user5->setEmail('test@australia.de');
        $user5->setCreatedAt(new \DateTimeImmutable());
        $user5->setKeycloakId('c8d51f2d-834c-4de5-87fa-87c7d9e5fd19');
        $user5->setFirstName('Test');
        $user5->setLastName('User');
        $user5->setRegisterId('ce351e33-b7b6-4a47-9649-3cb91f151c42');
        $user5->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234', 'telephoneNumber' => '0123456789']);
        $user5->setTimeZone('Australia/Lindeman');
        $user5->setUuid('aa58c86f-8a0b-403d-8b3c-f1c85b911155');
        $user5->setUid('43160891-0802-4073-97ca-cd472486b769');
        $user5->setUsername('test@australia.de');
        $user5->setCreatedAt(new \DateTimeImmutable());
        $user5->setIndexer('test@australia.de test@australia.de test user 1234 test1 0123456789');
        $manager->persist($user5);

        $user6 = new \App\Entity\User();
        $user6->setEmail('test@noTimeZone.de');
        $user6->setCreatedAt(new \DateTimeImmutable());
        $user6->setKeycloakId('31478228-80ca-4ce2-97ad-a24414dbc709');
        $user6->setFirstName('Test');
        $user6->setLastName('User');
        $user6->setRegisterId('4f634be0-4e4c-49bc-8caf-751ba57102f2');
        $user6->setSpezialProperties(['ou' => 'Test1', 'departmentNumber' => '1234', 'telephoneNumber' => '0123456789']);
        $user6->setUuid('217b9ab9-c2c7-445d-9c5e-f44b79a4a28f');
        $user6->setUid('2c41a124-b8de-4074-83ae-3c0f786cd11b');
        $user6->setUsername('test@noTimeZone.de');
        $user6->setCreatedAt(new \DateTimeImmutable());
        $user6->setIndexer('test@noTimeZone.de test@noTimeZone.de test user 1234 test1 0123456789');
        $manager->persist($user6);

        $user->addAddressbook($user2);
        $user->addAddressbook($user3);

        $deputy1 = new Deputy();
        $deputy1->setManager($user5)
            ->setDeputy($user6)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsFromLdap(true);
        $deputy2 = new Deputy();
        $deputy2->setManager($user5)
            ->setDeputy($user6)
            ->setCreatedAt(new \DateTimeImmutable())
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
        $serverOriginal->setAppSecret('jitsiSecretjitsiSecretjitsiSecret12');
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
        $server->setAppSecret('jitsiSecretjitsiSecretjitsiSecret12');
        $server->setAppId('');
        $server->setJwtModeratorPosition(0);
        $server->setPrivacyPolicy('https://privacy.dev');
        $server->setServerName('Server no JWT');

        $manager->persist($server);
        $manager->flush();

        $license = new License();
        $license->setUrl('meet.jit.si2');
        $license->setLicense(
            '{"signature":"4b1e1205ad1a492f33646c2f499c11b0252335b16da64d60804f6e0a5ca55b4c811bbad8faf0c3aef41a51ee94b5f93d4d2f5852e35a557280f06794bccbd5ee4cdd8e894f5d474f36b6127f77198a0a28cf369c447963f45b41ada180de36e309ea18b060dadfae53599118443c849cd86b78907d05ef5f376075c6bb7063682fbd05df57d3ec74b72b14f89bf2dc3defa8a2181bb12f0b5feef1e8cc731606b0e6c28a9e0d39c46d04cad228ab825457d79f8ec4047f5b8476fba742e18778b1934076767cc0e6fb874e865d1ac6ae5034282a9952c6091cf9f0bf16739c72c52e7e2d00ecad797cc3cc30f841dc3d0c51134a2a5200a40cec93c76e32038beaf4210973f3c946da8aeb06cb9c09d6bbb3e9137a5d88f3bf38f9ecfdab02117edc054161d2345ccc9d15bd9c59e696998e9d102d77ca2548f872a44f3150f81b24a28e741ee85ae99b24fd1938d5bcf906016ccf09a4f20da468113181b3b4653b2eff5ffc628692dd720c62fd063f5baa13c1a9ab60e88cc462efa15612bbbed1780a9c46ce851f422c7e5dd861f1aa7304d3cb87331d12e67496b39703d3e62f8305381343ee54f6dde8718a83581a12edceedbc0543f1ae226c1ae4acdaaa2ed09191593164dd2635319c09da53803d26a5cf14a84fb35a73d8688fdad251e33ed4719ee9d4281247d0cb1adfa62b220257e396a061d5598a4a401551dc","entry":{"valid_until":"2024-07-04","server_url":"meet.jit.si2","license_key":"f5c627f7ac98bef45fcfdd5fcade0246"}}'
        );
        $license->setValidUntil(new \DateTimeImmutable('2024-07-04'));
        $license->setLicenseKey('f5c627f7ac98bef45fcfdd5fcade0246');
        $manager->persist($license);
        $manager->flush();
        $server = new Server();
        $server->setUrl('meet.jit.si2');
        $server->setAdministrator($user);
        $server->addUser($user);
        $server->setSlug('test2');
        $server->setLogoUrl('https://test.img');
        $server->setAppSecret('jitsiSecretjitsiSecretjitsiSecret12');
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
            $start = new \DateTimeImmutable()->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('+' . ($i * 2 + 1) . 'minutes');
            $end   = $start->modify('+60min');
            $room->setStart($start);
            $room->setEnddate($end);
            $room->addUser($user);
            $room->addUser($user2);
            $room->addUser($user3);
            $room->setUid(self::REMINDER_UIDS[$i]);
            $room->setUidReal(self::REMINDER_UIDREALS[$i]);
            $room->setSlug('test');
            $room->setScheduleMeeting(false);
            $room->setName('TestMeeting: ' . $i);
            $room->setSequence(0);
            $room->setServer($server);
            $callerRoom = new CallerRoom();
            $callerRoom->setCallerId('1234' . $i);
            $callerRoom->setCreatedAt(new \DateTimeImmutable());
            $room->setCallerRoom($callerRoom);
            $room->setHostUrl('http://localhost:8000');
            $manager->persist($room);
        }
        $start = new \DateTimeImmutable('2021-01-01T15:00');
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
            $room->setUid(UidGeneratorHelper::generate());
            $room->setUidReal(UidGeneratorHelper::generate());
            $room->setSlug('test');
            $room->setScheduleMeeting(true);
            $room->setName('Termin finden: ' . $i);
            $room->setSequence(0);
            $room->setServer($server);
            $selectDate = new Scheduling();
            $selectDate->setUid(UidGeneratorHelper::generate());
            $selectDate->setDescription('test');
            $selectDate->setRoom($room);
            for ($c = 0; $c < 5; $c++) {
                $time = new SchedulingTime();
                $time->setTime($start);
                $start = $start->modify('+1day');
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
            $start = new \DateTimeImmutable()->setTimezone(new \DateTimeZone('America/Adak'))->modify('+' . ($i * 2) . 'minutes');
            $end   = $start->modify('+60min');
            $room->setStart($start);
            $room->setEnddate($end);
            $room->addUser($user);
            $room->addUser($user2);
            $room->addUser($user3);
            $room->setUid(UidGeneratorHelper::generate());
            $room->setUid(UidGeneratorHelper::generate());
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
        $callerRoom->setCreatedAt(new \DateTimeImmutable());
        $room->setCallerRoom($callerRoom);
        $room->setCallerRoom($callerRoom);
        $room->setAgenda('Testagenda:');
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = new \DateTimeImmutable()->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $end   = $start->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setUid(UidGeneratorHelper::generate());
        $room->setUidReal(UidGeneratorHelper::generate());
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
        $start = new \DateTimeImmutable('tomorrow')->setTimezone(new \DateTimeZone('Europe/Berlin'))->setTime(10, 0);
        $end   = $start->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->setUid('ccd854d7-7d27-4633-a072-9f89cdbf7d65');
        $room->setUidReal(UidGeneratorHelper::generate());
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
        $start = new \DateTimeImmutable('yesterday')->setTimezone(new \DateTimeZone('Europe/Berlin'))->setTime(10, 0);
        $end   = $start->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->setCreator($user);
        $room->addUser($user2);
        $room->setUid(UidGeneratorHelper::generate());
        $room->setUidReal(UidGeneratorHelper::generate());
        $room->setSlug('test5');
        $room->setScheduleMeeting(false);
        $room->setName('Room Yesterday');
        $room->setSequence(0);
        $room->setServer($server);
        $callerRoom = new CallerRoom();
        $callerRoom->setCallerId('123456');
        $callerRoom->setCreatedAt(new \DateTimeImmutable());
        $room->setCallerRoom($callerRoom);
        $manager->persist($room);
        $manager->flush();

        $room = new Rooms();
        $room->setTimeZone('Europe/Berlin');
        $room->setAgenda('Testagenda:' . $i);
        $room->setDuration(60);
        $room->setDissallowPrivateMessage(true);
        $room->setDissallowScreenshareGlobal(true);
        $start = new \DateTimeImmutable()->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('-10min');
        $end   = $start->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->setUid('2660b602-2824-43cc-b743-39f170652943');
        $room->setUidReal(UidGeneratorHelper::generate());
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
            ->setRoomCreatedAt(new \DateTimeImmutable())
            ->setRoom($room)
            ->setJitsiRoomId('test@test.de')
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($roomStatus);
        $manager->flush();

        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt(new \DateTimeImmutable())
            ->setInRoom(true)
            ->setParticipantId('inderKonferenz@test.de')
            ->setParticipantName('in der Konferenz')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTimeImmutable());
        $manager->persist($roomStatusPart);
        $manager->flush();
        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt(new \DateTimeImmutable())
            ->setInRoom(false)
            ->setParticipantId('inderKonferenz3@test.de')
            ->setParticipantName('aus der Konferenz 1 Stunde')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTimeImmutable())
            ->setLeftRoomAt(new \DateTimeImmutable()->modify('+1hour'));
        $manager->persist($roomStatusPart);
        $manager->flush();

        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt(new \DateTimeImmutable())
            ->setInRoom(false)
            ->setParticipantId('inderKonferenz3@test.de')
            ->setParticipantName('aus der Konferenz 1 Tag')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTimeImmutable())
            ->setLeftRoomAt(new \DateTimeImmutable()->modify('+1day'));
        $manager->persist($roomStatusPart);
        $manager->flush();


        $roomStatus = new RoomStatus();
        $roomStatus->setCreated(true)
            ->setRoomCreatedAt(new \DateTimeImmutable()->modify('-2hours'))
            ->setRoom($room)
            ->setJitsiRoomId('test@test.de')
            ->setUpdatedAt(new \DateTimeImmutable())
            ->setCreatedAt(new \DateTimeImmutable())
            ->setDestroyed(true)
            ->setDestroyedAt(new \DateTimeImmutable()->modify('-1hour'));
        $manager->persist($roomStatus);
        $manager->flush();

        $roomStatusPart = new RoomStatusParticipant();
        $roomStatusPart->setEnteredRoomAt(new \DateTimeImmutable()->modify('-2hours'))
            ->setInRoom(false)
            ->setLeftRoomAt(new \DateTimeImmutable()->modify('-1hour'))
            ->setParticipantId('inderKonferenz@test.de')
            ->setParticipantName('beim letzen mal')
            ->setRoomStatus($roomStatus)
            ->setEnteredRoomAt(new \DateTimeImmutable())
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
        $room->setUid('c6f40763-d28f-40f6-85c9-2566042ea4bd');
        $room->setUidReal('6f6cff93-e324-41e4-98ab-7edef502f477');
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
        $room->setUid('e06ec9c5-7ddd-464d-b6da-8cff8138b63f');
        $room->setUidReal('459164c0-a5e8-4c52-aa07-43c1a75c0910');
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
        $room->setUid('9860a9ad-833a-45f7-a010-6e463ae1e2f1');
        $room->setUidReal('5f17d274-a1df-4b87-9c14-be6106b7f379');
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
        $start = new \DateTimeImmutable('tomorrow')->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $end   = $start->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->setUid('3e9e41aa-2883-45ab-83a7-e3c8f9f655c2');
        $room->setUidReal('4328e3cb-4f33-4ae1-8932-2a4397f23b13');
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
        $start = new \DateTimeImmutable('tomorrow')->setTimezone(new \DateTimeZone('Europe/Berlin'));
        $end   = $start->modify('+60min');
        $room1->setStart($start);
        $room1->setEnddate($end);
        $room1->setModerator($user);
        $room1->setCreator($user);
        $room1->addUser($user);
        $room1->setUid('e761af5d-b686-4ff5-b05f-d263a9572c0c');
        $room1->setUidReal('2678924b-b3c6-4d18-8945-466cfcb61355');
        $room1->setSlug('test534');
        $room1->setScheduleMeeting(false);
        $room1->setName('Room with Start and no Participants list and Lobby Activated');
        $room1->setSequence(0);
        $room1->setTotalOpenRooms(true);
        $room1->setLobby(true);
        $room1->setServer($server);
        $manager->persist($room1);
        $manager->flush();
        $lobbyTime = new \DateTimeImmutable();
        for ($i = 0; $i < 10; $i++) {
            $lobbyUser = new LobbyWaitungUser();
            $lobbyUser->setWebsocketReady(true);
            $lobbyUser->setUser($user);
            $lobbyUser->setRoom($room1);
            $lobbyUser->setUid(self::LOBBY_UIDS[$i]);
            $lobbyTime = $lobbyTime->modify('-1 hour');
            $lobbyUser->setCreatedAt($lobbyTime);
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
        $start = new \DateTimeImmutable()->setTimezone(new \DateTimeZone('Europe/Berlin'))->modify('-10min');
        $end   = $start->modify('+60min');
        $room->setStart($start);
        $room->setEnddate($end);
        $room->setModerator($user);
        $room->setCreator($user);
        $room->addUser($user);
        $room->addUser($user2);
        $room->addUser($user3);
        $room->setUid('ff17a3fe-58c3-485a-abc0-e48c15fc558c');
        $room->setUidReal('5245ba59-53e8-40c6-adad-acf196245cdb');
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
            ->setCreatedAt(new \DateTimeImmutable())
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
            ->setCreatedAt(new \DateTimeImmutable())
            ->setPriority(0);
        $manager->persist($predefined1);
        $predefined2 = new PredefinedLobbyMessages();
        $predefined2->setActive(false)
            ->setText('Bitte warten/Disabled!')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setPriority(1);
        $manager->persist($predefined2);
        $predefined3 = new PredefinedLobbyMessages();
        $predefined3->setActive(true)
            ->setText('Wir haben andere Themen!')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setPriority(2);

        $manager->persist($predefined3);
        $manager->flush();
    }
}
