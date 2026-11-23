<?php

namespace App\Tests\Twig\Runtime;

use App\Entity\Recording;
use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RecordingRepository;
use App\Twig\Runtime\LivekitRecordingRuntime;
use PHPUnit\Framework\TestCase;

class LivekitRecordingRuntimeTest extends TestCase
{
    public function testReturnsNullAndDoesNotQueryWhenUserIsMissing(): void
    {
        $repository = $this->createMock(RecordingRepository::class);
        $repository->expects($this->never())->method('findOneBy');
        $runtime = new LivekitRecordingRuntime($repository);

        $this->assertNull($runtime->getRecordingForRoomAndUser(null, new Rooms()));
    }

    public function testReturnsNullAndDoesNotQueryWhenRoomIsMissing(): void
    {
        $repository = $this->createMock(RecordingRepository::class);
        $repository->expects($this->never())->method('findOneBy');
        $runtime = new LivekitRecordingRuntime($repository);

        $this->assertNull($runtime->getRecordingForRoomAndUser(new User(), null));
    }

    public function testReturnsRecordingForRoomAndUser(): void
    {
        $user = (new User())->setUid('user-1');
        $room = (new Rooms())->setUid('room-1');
        $recording = (new Recording())->setRoom($room)->setUser($user)->setUid('recording-1');

        $repository = $this->createMock(RecordingRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['room' => $room, 'user' => $user])
            ->willReturn($recording);
        $runtime = new LivekitRecordingRuntime($repository);

        $this->assertSame($recording, $runtime->getRecordingForRoomAndUser($user, $room));
    }
}
