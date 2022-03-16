<?php

namespace App\Tests;

use App\Repository\RoomsRepository;
use App\Service\webhook\RoomWebhookService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JitsiEventsServiceTest extends KernelTestCase
{
    private $roomCreatedData;
    private $roomDestroyedData;
    private $participantJoinedData;
    private $participantLeftD;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->roomCreatedData = json_decode('{
                "event_name": "muc-room-created",
            "created_at": 1647337556,
            "room_name": "123456780",
            "room_jid": "123456780@conference.testserver.de"
          }', true);
        $this->roomDestroyedData = json_decode('{
            "all_occupants": [
              {
                "occupant_jid": "123456780@conference.testserver.de",
                "joined_at": 1647337556,
                "name": "TEst USer in the Meeting",
                "left_at": 1647337563
              }
            ],
            "event_name": "muc-room-destroyed",
            "room_jid": "123456780@conference.testserver.de",
            "room_name": "123456780",
            "created_at": 1647337556,
            "destroyed_at": 1647337563
          }', true);


        $this->participantLeftD = json_decode('
            {
                "event_name": "muc-occupant-left",
        "occupant": {
                "occupant_jid": "653515c2-21a2-4d9b-9e44-b557cdf8f4ae@jitsi01/OxCwsxE2",
          "joined_at": 1647337850,
          "name": "TEst USer in the Meeting",
          "left_at": 1647337866
        }}', true);

        $this->participantJoinedData = json_decode('
                {
                    "event_name": "muc-occupant-joined",
            "occupant": {
                    "occupant_jid": "653515c2-21a2-4d9b-9e44-b557cdf8f4ae@jitsi01/OxCwsxE2",
              "joined_at": 1647337850,
              "name": "TEst USer in the Meeting"
            },
            "room_name": "123456780",
            "room_jid": "123456780@conference.testserver.de"
          }', true);

    }

    public function testroomCreatedWebhook(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(true, $room->getRoomstatuses()[0]->getCreated());
        self::assertEquals(null, $room->getRoomstatuses()[0]->getDestroyed());
        self::assertEquals(null, $room->getRoomstatuses()[0]->getDestroyedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getUpdatedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getCreated());
        self::assertEquals($this->roomCreatedData['created_at'], $room->getRoomstatuses()[0]->getRoomCreatedAt()->getTimestamp());
    }

    public function testroomDestroyWebhook(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->roomDestroyedData));
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
        self::assertEquals(true, $room->getRoomstatuses()[0]->getCreated());
        self::assertNotNull($room->getRoomstatuses()[0]->getDestroyedAt());
        self::assertEquals(true, $room->getRoomstatuses()[0]->getDestroyed());
        self::assertNotNull($room->getRoomstatuses()[0]->getUpdatedAt());
        self::assertNotNull($room->getRoomstatuses()[0]->getCreated());
        self::assertEquals($this->roomDestroyedData['destroyed_at'], $room->getRoomstatuses()[0]->getDestroyedAt()->getTimestamp());
    }

    public function testroomParticipantEnteredWebhook(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->participantJoinedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(true, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
    }
    public function testroomParticipantLeaveWebhook(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->participantJoinedData));
        self::assertTrue($webhookService->startWebhook($this->participantLeftD));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
    }
    public function testroomParticipantCorrectWorkflow(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->participantJoinedData));
        self::assertTrue($webhookService->startWebhook($this->participantLeftD));
        self::assertTrue($webhookService->startWebhook($this->roomDestroyedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
    }
    public function testroomParticipantWrongDirectionWorkflow(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->participantJoinedData));
        self::assertTrue($webhookService->startWebhook($this->roomDestroyedData));
        self::assertTrue($webhookService->startWebhook($this->participantLeftD));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        $roomStatus = $room->getRoomstatuses()[0];
        self::assertEquals(1, sizeof($roomStatus->getRoomStatusParticipants()));
        self::assertEquals(false, $roomStatus->getRoomStatusParticipants()[0]->getInRoom());
    }
    public function testroomWrongdata(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertFalse($webhookService->roomCreated($this->roomDestroyedData));
        self::assertFalse($webhookService->roomParticipantJoin($this->participantLeftD));
        self::assertFalse($webhookService->roomParticipantLeft($this->roomCreatedData));
        self::assertFalse($webhookService->roomDestroyed($this->participantJoinedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(0, sizeof($room->getRoomstatuses()));
    }
    public function testroomWrongdataCreate(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        $this->roomCreatedData['room_name']='0000';
        self::assertFalse($webhookService->startWebhook($this->roomCreatedData));
       unset($this->roomCreatedData['event_name']);
        self::assertFalse($webhookService->startWebhook($this->roomCreatedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(0, sizeof($room->getRoomstatuses()));
    }
    public function testroomDoubledataCreate(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertFalse($webhookService->startWebhook($this->roomCreatedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }
    public function testroomDoubledataCreatefromRoom(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        $this->roomCreatedData['room_jid'] = '000';
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(2, sizeof($room->getRoomstatuses()));
        self::assertEquals(true, $room->getRoomstatuses()[0]->getDestroyed());
        self::assertEquals(null, $room->getRoomstatuses()[1]->getDestroyed());
    }
    public function testroomDoubleDestroy(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->roomDestroyedData));
        self::assertFalse($webhookService->startWebhook($this->roomDestroyedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }
    public function testroomWrongdataDestroy(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        $this->roomDestroyedData['room_jid']='0000';
        self::assertFalse($webhookService->startWebhook($this->roomDestroyedData));
        unset($this->roomDestroyedData['event_name']);
        self::assertFalse($webhookService->startWebhook($this->roomDestroyedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }
    public function testroomWrongdataJoin(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->participantJoinedData));
        self::assertFalse($webhookService->startWebhook($this->participantJoinedData));
        $this->participantJoinedData['room_jid']='0000';
        self::assertFalse($webhookService->startWebhook($this->participantJoinedData));
        unset($this->participantJoinedData['event_name']);
        self::assertFalse($webhookService->startWebhook($this->participantJoinedData));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }
    public function testroomWrongdataLeft(): void
    {
        $kernel = self::bootKernel();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $webhookService = self::getContainer()->get(RoomWebhookService::class);
        self::assertTrue($webhookService->startWebhook($this->roomCreatedData));
        self::assertTrue($webhookService->startWebhook($this->participantJoinedData));
        $this->participantLeftD['occupant']['occupant_jid']='0000';
        self::assertFalse($webhookService->startWebhook($this->participantLeftD));
        unset($this->participantLeftD['event_name']);
        self::assertFalse($webhookService->startWebhook($this->participantLeftD));
        $room = $roomRepo->findOneBy(array('uid' => '123456780'));
        self::assertEquals(1, sizeof($room->getRoomstatuses()));
    }
}
