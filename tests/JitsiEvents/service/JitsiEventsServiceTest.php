<?php

namespace App\Tests\JitsiEvents\service;

use App\Entity\RoomStatusParticipant;
use App\Repository\RoomsRepository;
use App\Repository\RoomStatusRepository;
use App\Service\Webhook\RoomWebhookService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JitsiEventsServiceTest extends KernelTestCase
{
    public static $roomCreatedData = [
        "event_name" => "muc-room-created",
        "created_at" => 1647337556,
        "room_name" => "bed6ca41-512f-409f-95c1-4ad7e9b73018",
        "is_breakout" => false,
        "room_jid" => "bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de"
    ];
    public static $roomDestroyedData = [
        "all_occupants" => [
            [
                "occupant_jid" => "bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de",
                "joined_at" => 1647337556,
                "name" => "TEst USer in the Meeting",
                "left_at" => 1647337563,
                'total_dominant_speaker_time' => 1023312
            ]
        ],
        "event_name" => "muc-room-destroyed",
        "room_jid" => "bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de",
        "room_name" => "bed6ca41-512f-409f-95c1-4ad7e9b73018",
        "is_breakout" => false,
        "created_at" => 1647337556,
        "destroyed_at" => 1647337563
    ];
    public static $participantJoinedData = [
        "event_name" => "muc-occupant-joined",
        "occupant" => [
            "occupant_jid" => "653515c2-21a2-4d9b-9e44-b557cdf8f4ae@jitsi01/OxCwsxE2",
            "joined_at" => 1647337850,
            "name" => "TEst USer in the Meeting"
        ],
        "room_name" => "bed6ca41-512f-409f-95c1-4ad7e9b73018",
        "is_breakout" => false,
        "room_jid" => "bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de"
    ];
    public static $participantLeftD = [
        "event_name" => "muc-occupant-left",
        "is_breakout" => false,
        "occupant" => [
            "occupant_jid" => "653515c2-21a2-4d9b-9e44-b557cdf8f4ae@jitsi01/OxCwsxE2",
            "joined_at" => 1647337850,

            "name" => "TEst USer in the Meeting",
            "left_at" => 1647337866,
            'total_dominant_speaker_time' => 12345678
        ]
    ];

    public function testroomCreatedWebhook(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(true, $room->getRoomstatuses()[0]->getCreated());
        self::assertEquals(null, $room->getRoomstatuses()[0]->getDestroyed());
        self::assertEquals(null, $room->getRoomstatuses()[0]->getDestroyedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getUpdatedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getCreated());
        self::assertEquals(JitsiEventsServiceTest::$roomCreatedData['created_at'], $room->getRoomstatuses()[0]->getRoomCreatedAt()->getTimestamp());
    }

    public function testroomCreatedWebhookCaseInsensitive(): void
    {
        $kernel = self::bootKernel();

        $webhookService = self::getContainer()->get(RoomWebhookService::class);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $testData = self::$roomCreatedData;
        $testData['room_name'] = strtoupper('ccd854d7-7d27-4633-a072-9f89cdbf7d65');
        self::assertNull($webhookService->startWebhook($testData));
        $room = $roomRepo->findOneBy(['uid' => 'ccd854d7-7d27-4633-a072-9f89cdbf7d65']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(true, $room->getRoomstatuses()[0]->getCreated());
        self::assertEquals(null, $room->getRoomstatuses()[0]->getDestroyed());
        self::assertEquals(null, $room->getRoomstatuses()[0]->getDestroyedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getUpdatedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getCreated());
        self::assertEquals(JitsiEventsServiceTest::$roomCreatedData['created_at'], $room->getRoomstatuses()[0]->getRoomCreatedAt()->getTimestamp());
    }

    public function testroomDestroyWebhook(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(true, $room->getRoomstatuses()[0]->getCreated());
        self::assertNotNull($room->getRoomstatuses()[0]->getDestroyedAt());
        self::assertEquals(true, $room->getRoomstatuses()[0]->getDestroyed());
        self::assertNotNull($room->getRoomstatuses()[0]->getUpdatedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getCreated());
        self::assertEquals(JitsiEventsServiceTest::$roomDestroyedData['destroyed_at'], $room->getRoomstatuses()[0]->getDestroyedAt()->getTimestamp());
    }

    public function testroomParticipantEnteredWebhook(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(true, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
        self::assertNull($roomStatus->getRoomStatusParticipants()[0]->getDominantSpeakerTime());
    }

    public function testroomParticipantLeaveWebhook(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
        self::assertEquals(12345678, $roomStatus->getRoomStatusParticipants()[0]->getDominantSpeakerTime());
    }

    public function testroomParticipantLeaveNoDOminantSpeakerTimeWebhook(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        $testData = JitsiEventsServiceTest::$participantLeftD;
        unset($testData['occupant']['total_dominant_speaker_time']);
        self::assertNull($webhookService->startWebhook($testData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
        self::assertNull($roomStatus->getRoomStatusParticipants()[0]->getDominantSpeakerTime());
    }


    public function testroomParticipantCorrectWorkflow(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
        self::assertEquals(12345678, $roomStatus->getRoomStatusParticipants()[0]->getDominantSpeakerTime());
    }

    public function testroomParticipantCorrectWorkflowWithBreakoutRoom(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));

        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
        self::assertEquals(12345678, $roomStatus->getRoomStatusParticipants()[0]->getDominantSpeakerTime());


        $breakoutRoomJoinData = self::$participantJoinedData;
        $breakoutRoomJoinData['is_breakout'] = true;
        $breakoutRoomJoinData['breakout_room_id'] = '98127398721';
        $createBreaktoutRoomData = self::$roomCreatedData;
        $createBreaktoutRoomData['is_breakout'] = true;
        $createBreaktoutRoomData['breakout_room_id'] = '98127398721';
        self::assertEquals('Room is a breakout room; we don`t create a status', $webhookService->startWebhook($createBreaktoutRoomData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals('Room is a breakout room we don`t join the participant', $webhookService->startWebhook($breakoutRoomJoinData));
        $breakoutLeaveRoomData = self::$participantLeftD;
        $breakoutLeaveRoomData['is_breakout'] = true;
        $breakoutLeaveRoomData['breakout_room_id'] = '98127398721';
        self::assertEquals('Room is a breakout room; we don`t remove the participant', $webhookService->startWebhook($breakoutLeaveRoomData));
        $closeBreakoutRoom = self::$roomDestroyedData;
        $closeBreakoutRoom['is_breakout'] = true;
        $closeBreakoutRoom['breakout_room_id'] = '98127398721';
        self::assertEquals('Room is a breakout room we don`t remove the main room', $webhookService->startWebhook($closeBreakoutRoom));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));

        $newPartJoind = self::$participantJoinedData;
        $newPartJoind['occupant']['occupant_jid'] = 'kjdshnfkjhds';
        self::assertNull($webhookService->startWebhook($newPartJoind));

        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(2, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
        self::assertEquals(12345678, $roomStatus->getRoomStatusParticipants()[0]->getDominantSpeakerTime());


        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(2, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[1]->getInRoom());
        self::assertEquals(12345678, $roomStatus->getRoomStatusParticipants()[0]->getDominantSpeakerTime());
    }


    public function testroomParticipantNoNameCorrectWorkflow(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        $testJoin = self::$participantJoinedData;
        unset($testJoin['occupant']['name']);
        self::assertEquals('NO_DATA', $webhookService->startWebhook($testJoin));
        self::assertEquals('Wrong occupant ID. The occupant is not in the database', $webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(0, sizeof($roomStatus->getRoomStatusParticipants()));
    }

    public function testroomParticipantCorrectWorkflowTwoRoomsCreated(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));

        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        // The same occupant ID joining a new session of the room is a new join and gets its own entry.
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        self::assertEquals('The occupant already left the room. It cannot leave the room twice', $webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        $test = JitsiEventsServiceTest::$participantJoinedData;
        $test['occupant']['occupant_jid'] = '000';
        self::assertNull($webhookService->startWebhook($test));
        $testLEft = JitsiEventsServiceTest::$participantLeftD;
        self::assertEquals('The occupant already left the room. It cannot leave the room twice', $webhookService->startWebhook($testLEft));
        $testLEft['occupant']['occupant_jid'] = '000';
        self::assertNull($webhookService->startWebhook($testLEft));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);

        self::assertEquals(2, sizeof($room->getRoomstatuses()));
        self::assertEquals(1, sizeof($room->getRoomstatuses()[0]->getRoomStatusParticipants()));
        self::assertEquals(2, sizeof($room->getRoomstatuses()[1]->getRoomStatusParticipants()));
        self::assertEquals(false, $room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getInRoom());
        foreach ($room->getRoomstatuses()[1]->getRoomStatusParticipants() as $participant) {
            self::assertEquals(false, $participant->getInRoom());
        }
    }

    /**
     * A participant leaving and re-entering an open room (new connection, or returning from a
     * breakout room with the same occupant ID) must produce one entry/leave pair per visit.
     */
    public function testroomParticipantRejoinCreatesSeparateEntries(): void
    {
        self::bootKernel();
        $roomRepo       = self::getContainer()->get(RoomsRepository::class);
        $webhookService = self::getContainer()->get(RoomWebhookService::class);

        self::assertNull($webhookService->startWebhook(self::$roomCreatedData));

        // 1st visit
        self::assertNull($webhookService->startWebhook(self::$participantJoinedData));
        self::assertNull($webhookService->startWebhook(self::$participantLeftD));

        // 2nd visit, same occupant ID (e.g. back from a breakout room)
        $join2                          = self::$participantJoinedData;
        $join2['occupant']['joined_at'] = 1647338000;
        self::assertNull($webhookService->startWebhook($join2));
        self::assertEquals('The occupant already joind with the same occupant ID', $webhookService->startWebhook($join2));
        $left2                        = self::$participantLeftD;
        $left2['occupant']['left_at'] = 1647338100;
        self::assertNull($webhookService->startWebhook($left2));

        // 3rd visit, new occupant ID (new connection) – still in the room when it closes
        $join3                             = self::$participantJoinedData;
        $join3['occupant']['occupant_jid'] = '653515c2-21a2-4d9b-9e44-b557cdf8f4ae@jitsi01/NewConn1';
        $join3['occupant']['joined_at']    = 1647338200;
        self::assertNull($webhookService->startWebhook($join3));

        $destroyed                 = self::$roomDestroyedData;
        $destroyed['destroyed_at'] = 1647338300;
        self::assertNull($webhookService->startWebhook($destroyed));

        $room   = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $status = $room->getRoomstatuses()[0];
        self::assertCount(1, $room->getRoomstatuses());

        $visits = $status->getRoomStatusParticipants()->toArray();
        usort($visits, fn(RoomStatusParticipant $a, RoomStatusParticipant $b) => $a->getEnteredRoomAt() <=> $b->getEnteredRoomAt());
        self::assertCount(3, $visits);

        $expected = [
            [1647337850, 1647337866],
            [1647338000, 1647338100],
            [1647338200, 1647338300],
        ];
        foreach ($expected as $i => [$entered, $left]) {
            self::assertEquals('TEst USer in the Meeting', $visits[$i]->getParticipantName());
            self::assertEquals($entered, $visits[$i]->getEnteredRoomAt()->getTimestamp());
            self::assertEquals($left, $visits[$i]->getLeftRoomAt()->getTimestamp());
            self::assertFalse($visits[$i]->getInRoom());
        }
    }

    public function testroomParticipantWrongDirectionWorkflow(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        self::assertEquals('Room Jitsi ID not found', $webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertEquals('Wrong occupant ID. The occupant is not in the database', $webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(0, sizeof($roomStatus->getRoomStatusParticipants()));
    }

    public function testroomJoinedToLate(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertEquals(null, $webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertEquals(null, $webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        self::assertEquals('Room Jitsi ID not found', $webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertEquals('Wrong occupant ID. The occupant is not in the database', $webhookService->startWebhook(JitsiEventsServiceTest::$participantLeftD));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(0, sizeof($room->getRoomstatuses()[0]->getRoomStatusParticipants()));
    }

    public function testroomNoRoomfoundCreateAndDestroy(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $roomStattusRepo  = self::getContainer()->get(RoomStatusRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        $testData = JitsiEventsServiceTest::$roomCreatedData;
        $testData['room_name'] = '0000';
        self::assertEquals(null, $webhookService->startWebhook($testData));
        $roomStatus = $roomStattusRepo->findBy(['jitsiRoomId' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de']);
        self::assertEquals(1, count($roomStatus));
        self::assertNull($roomStatus[0]->getRoom());
        self::assertEquals(null, $webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        $roomStatus = $roomStattusRepo->findBy(['jitsiRoomId' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de']);
        self::assertEquals(1, count($roomStatus[0]->getRoomStatusParticipants()));

        self::assertEquals(null, $webhookService->startWebhook(self::$roomDestroyedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(0, sizeof($room->getRoomstatuses()));
        $roomStatus = $roomStattusRepo->findBy(['jitsiRoomId' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de']);
        self::assertEquals(0, count($roomStatus));
    }

    public function testroomNoRoomfoundCreate(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        $testData = JitsiEventsServiceTest::$roomCreatedData;
        $testData['room_name'] = '0000';
        self::assertEquals(null, $webhookService->startWebhook($testData));
        unset($testData['event_name']);
        self::assertEquals('No event defined', $webhookService->startWebhook($testData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(0, sizeof($room->getRoomstatuses()));
        $roomStattusRepo  = self::getContainer()->get(RoomStatusRepository::class);
        $roomStatus = $roomStattusRepo->findBy(['jitsiRoomId' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018@conference.testserver.de']);
        self::assertEquals(1, count($roomStatus));
        self::assertNull($roomStatus[0]->getRoom());
    }

    public function testroomDoubledataCreate(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertEquals('Room already created', $webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }

    public function testroomDoubledataCreatefromRoom(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        $testData = JitsiEventsServiceTest::$roomCreatedData;
        self::assertNull($webhookService->startWebhook($testData));
        $testData['room_jid'] = '000';
        self::assertEquals('Room already created', $webhookService->startWebhook($testData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(null, $room->getRoomstatuses()[0]->getDestroyed());
    }

    public function testroomDoubleDestroy(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        self::assertEquals('Room Jitsi ID not found', $webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }

    public function testroomWrongdataDestroy(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);

        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        $test = JitsiEventsServiceTest::$roomDestroyedData;
        $test['room_jid'] = '0000';
        self::assertEquals('Room Jitsi ID not found', $webhookService->startWebhook($test));
        unset($test['event_name']);
        self::assertEquals('No event defined', $webhookService->startWebhook($test));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }

    public function testroomWrongdataJoin(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        self::assertEquals('The occupant already joind with the same occupant ID', $webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        $testData = JitsiEventsServiceTest::$participantJoinedData;
        $testData['room_jid'] = '0000';
        self::assertEquals('Room Jitsi ID not found', $webhookService->startWebhook($testData));
        unset($testData['event_name']);
        self::assertEquals('No event defined', $webhookService->startWebhook($testData));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(1, sizeof($room->getRoomstatuses()[0]->getRoomStatusParticipants()));
    }

    public function testroomWrongdataLeft(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        $testData = JitsiEventsServiceTest::$participantJoinedData;
        self::assertNull($webhookService->startWebhook($testData));
        $testData2 = JitsiEventsServiceTest::$participantLeftD;
        $testData2['occupant']['occupant_jid'] = '0000';
        self::assertEquals('Wrong occupant ID. The occupant is not in the database', $webhookService->startWebhook($testData2));
        unset($testData2['event_name']);
        self::assertEquals('No event defined', $webhookService->startWebhook($testData2));
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(1, sizeof($room->getRoomstatuses()[0]->getRoomStatusParticipants()));
    }

    public function testroomDestroyWebhookWithRestParticipants(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomCreatedData));
        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$participantJoinedData));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomPart = new RoomStatusParticipant();
        $roomStatus = $room->getRoomstatuses()[0];


        self::assertEquals(1, sizeof($room->getRoomstatuses()[0]->getRoomStatusParticipants()));
        self::assertEquals(true, $room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getInRoom());

        self::assertNotNull($room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getEnteredRoomAt());
        self::assertTrue($room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getInRoom());
        self::assertNull($room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getLeftRoomAt());

        self::assertNull($webhookService->startWebhook(JitsiEventsServiceTest::$roomDestroyedData));

        $room = $roomRepo->findOneBy(['uid' => 'bed6ca41-512f-409f-95c1-4ad7e9b73018']);
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertNotNull($room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getEnteredRoomAt());
        self::assertFalse($room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getInRoom());
        self::assertNotNull($room->getRoomstatuses()[0]->getRoomStatusParticipants()[0]->getLeftRoomAt());
    }
}
