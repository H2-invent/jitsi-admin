<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Entity\Recording;
use App\Entity\Rooms;
use App\Entity\RoomStatus;
use App\Entity\RoomStatusParticipant;
use App\Entity\Server;
use App\Message\Provisioner\Enum\Status;
use App\Message\Provisioner\Enum\Type;
use App\Message\Provisioner\ProvisionerStatusMessage;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\Provisioner\ProvisionerCommunicationService;
use App\Service\ServerService;
use App\Tests\TransactionTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProvisionerServiceTest extends KernelTestCase
{
    use TransactionTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginTransaction();
    }

    public function testProvisionNewInstance_sendsMessage(): void
    {
        /** @var ProvisionerCommunicationService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerCommunicationService::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');

        $room = $roomsRepository->findOneBy([]);
        $record = (new \App\Entity\ProvisionerRecord())->setRoom($room);
        self::getContainer()->get(EntityManagerInterface::class)->persist($record);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $provisionerService->sendProvisionRequest($record);
        $sentMessages = $transport->getSent();

        self::assertCount(1, $sentMessages);
    }

    public function testProvisionNewInstance_savesOriginalServerId(): void
    {
        /** @var ProvisionerCommunicationService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerCommunicationService::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);

        $room = $roomsRepository->findOneBy([]);
        self::getContainer()->get(\App\Service\Provisioner\ProvisionerPersistenceService::class)
            ->saveOriginalServer($room);

        self::assertEquals($room->getServer(), $room->getOriginalServer());
    }

    /**
     * Tests for findProvisioningRecordsWhoseServerCanBeDeleted() via cleanupUnusedProvisionedServers()
     * Testing: server.isAllowedToCloneForAutoscale IS NULL
     */
    /**
     * Testing: server.isProvisioningEnabled = 1
     */
    /**
     * Testing: room.persistantRoom = true (persistent room should be eligible for cleanup)
     */
    /**
     * Testing: room.persistantRoom = false AND room.endDateUtc < :now (non-persistent room with past end date)
     */
    /**
     * Testing: room.persistantRoom = false AND room.endDateUtc >= :now (non-persistent room with future end date should be excluded)
     */
    /**
     * Testing: status_participant.inRoom = true (participant still in room should be excluded)
     */
    /**
     * Testing: status_participant.inRoom = false (participant left room should be included)
     */
    /**
     * Testing: status.destroyed = 1 (destroyed status should be included even with participant)
     */
    /**
     * Testing: recording.id IS NULL (no recording should be included)
     */
    /**
     * Testing: recording.user IS NULL (recording without user should be included)
     */
    /**
     * Testing: recording.user IS NOT NULL (active recording with user should be excluded)
     */
    /**
     * Testing: status_participant.inRoom IS NULL (no participant record should be included)
     */
    /**
     * Testing: Complete scenario - all conditions met for cleanup
     */
}
