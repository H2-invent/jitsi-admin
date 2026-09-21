<?php

namespace App\Tests\JitsiEvents\service;



use App\Entity\RoomStatus;
use App\Repository\RoomStatusRepository;
use App\Service\api\EventSyncApiService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JItsiEventSyncAPITest extends KernelTestCase
{
    private RoomStatusRepository&MockObject $roomStatusRepositoryMock;
    private EventSyncApiService $eventSyncApiService;

    protected function setUp(): void
    {
        $this->roomStatusRepositoryMock = $this->createMock(RoomStatusRepository::class);
        $this->eventSyncApiService = new EventSyncApiService($this->roomStatusRepositoryMock);
    }

    public function testGetCallerSessionFromUidRoomStarted(): void
    {
        // Arrange
        $uid = 'some_uid';
        $this->roomStatusRepositoryMock->expects($this->once())
            ->method('findRoomStatusByUid')
            ->with($uid)
            ->willReturn(new RoomStatus());

        // Act
        $result = $this->eventSyncApiService->getCallerSessionFromUid($uid);

        // Assert
        $this->assertEquals(['status' => 'ROOM_STARTED'], $result);
    }

    public function testGetCallerSessionFromUidRoomClosed(): void
    {
        // Arrange
        $uid = 'another_uid';
        $this->roomStatusRepositoryMock->expects($this->once())
            ->method('findRoomStatusByUid')
            ->with($uid)
            ->willReturn(null);

        // Act
        $result = $this->eventSyncApiService->getCallerSessionFromUid($uid);

        // Assert
        $this->assertEquals(['status' => 'ROOM_CLOSED'], $result);
    }
}
