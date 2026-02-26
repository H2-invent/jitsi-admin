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
use App\Service\ProvisionerService;
use App\Service\ServerService;
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
    private array $entitiesToCleanup = [];

    protected function tearDown(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        // Remove in reverse order to handle dependencies
        foreach (array_reverse($this->entitiesToCleanup) as $entity) {
            try {
                $entityManager->remove($entity);
            } catch (\Exception $e) {
                // Entity may already be removed or not managed
            }
        }
        $entityManager->flush();
        $this->entitiesToCleanup = [];

        parent::tearDown();
    }

    private function persistAndTrack(EntityManagerInterface $entityManager, object $entity): void
    {
        $entityManager->persist($entity);
        $this->entitiesToCleanup[] = $entity;
    }

    public function testProvisionNewInstance_sendsMessage(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');

        $room = $roomsRepository->findOneBy([]);
        $provisionerService->provisionNewServerForRoom($room);
        $sentMessages = $transport->getSent();

        self::assertCount(1, $sentMessages);
    }

    public function testProvisionNewInstance_savesOriginalServerId(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);

        $room = $roomsRepository->findOneBy([]);
        $provisionerService->provisionNewServerForRoom($room);

        self::assertEquals($room->getServer(), $room->getOriginalServer());
    }

    public function testSaveNewServerAndRedirect_savesNewServer(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);
        /** @var ServerRepository $serverRepository */
        $serverRepository = self::getContainer()->get(ServerRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $room = $roomsRepository->findOneBy([]);
        $randomString = uniqid();
        $statusMessage = new ProvisionerStatusMessage(
            $randomString,
            Type::PROVISION,
            Status::DONE,
            $randomString,
            $randomString,
            $randomString,
            $randomString,
        );
        $provisionerService->saveNewServerAndRedirect($room, $statusMessage);

        $newServer = $serverRepository->findOneBy([
            'url' => $randomString,
            'serverName' => $randomString,
            'appId' => $randomString,
            'appSecret' => $randomString,
        ]);
        self::assertNotNull($newServer);

        $entityManager->refresh($room);
        $roomServer = $room->getServer();
        self::assertEquals($newServer, $roomServer);
    }

    public function testSaveNewServerAndRedirect_sendsRedirect(): void
    {
        self::bootKernel();
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);
        /** @var DirectSendService $directSend */
        $directSend = self::getContainer()->get(DirectSendService::class);
        /** @var MessageBusInterface $messageBus */
        $messageBus = self::getContainer()->get(MessageBusInterface::class);
        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var ServerService $serverService */
        $serverService = self::getContainer()->get(ServerService::class);

        $room = $roomsRepository->findOneBy([]);
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update) use ($room): string {
                $updateData = json_decode($update->getData(), true, 512, JSON_THROW_ON_ERROR);
                self::assertSame('redirect_local', $updateData['type'] ?? null);
                self::assertContains(ProvisionerService::WEBSOCKET_TOPIC_NAME . $room->getUidReal(), $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);

        $randomString = uniqid();
        $statusMessage = new ProvisionerStatusMessage(
            $randomString,
            Type::PROVISION,
            Status::DONE,
            $randomString,
            $randomString,
            $randomString,
            $randomString,
        );
        $provisionerService = new ProvisionerService($directSend, $messageBus, $urlGenerator, $entityManager, $serverService, $roomsRepository);
        $provisionerService->saveNewServerAndRedirect($room, $statusMessage);
    }

    public function testRemoveServerAndRestoreOriginal_restoresOriginal(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var RoomsRepository $roomRepository */
        $roomRepository = self::getContainer()->get(RoomsRepository::class);

        $room = $roomRepository->findOneBy([]);
        $originalServer = $room->getServer();
        $room->setOriginalServer($originalServer);
        $server = new Server();

        $room->setServer($server);

        $provisionerService->removeServerAndRestoreOriginal($room);
        $roomAfter = $roomRepository->find($room->getId());

        self::assertNull($roomAfter->getOriginalServer());
        self::assertSame($originalServer, $roomAfter->getServer());
    }

    public function testRemoveServerAndRestoreOriginal_deletesServer(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var RoomsRepository $roomRepository */
        $roomRepository = self::getContainer()->get(RoomsRepository::class);
        /** @var ServerRepository $serverRepository */
        $serverRepository = self::getContainer()->get(ServerRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $room = $roomRepository->findOneBy([]);
        $server = (new Server())
            ->setUrl('url')
            ->setSlug('slug')
            ->setServerName('serverName')
            ->setJwtModeratorPosition(1)
        ;
        $entityManager->persist($server);
        $room->setOriginalServer($room->getServer());
        $room->setServer($server);
        $entityManager->flush();
        $serverId = $server->getId();

        $provisionerService->removeServerAndRestoreOriginal($room);

        $entityManager->clear();
        $deletedServer = $serverRepository->find($serverId);

        self::assertNull($deletedServer);
    }

    public function testRemoveServerAndRestoreOriginal_throwsOnNewRoom(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);

        $this->expectException(\RuntimeException::class);

        $room = new Rooms();
        $provisionerService->removeServerAndRestoreOriginal($room);
    }

    /**
     * Tests for findRoomsWhoseProvisionedServerCanBeDeleted() via cleanupUnusedProvisionedServers()
     * Testing: server.isAllowedToCloneForAutoscale IS NULL
     */
    public function testCleanupUnusedProvisionedServers_excludesServerWithAllowedToClone(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_allowed_clone')
            ->setUrl('url_allowed_clone')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(true) // NOT NULL, should be excluded
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_allowed_clone')
            ->setSlug('slug_allowed_clone')
            ->setUid('uid_allowed_clone')
            ->setUidReal('uid_real_allowed_clone')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should NOT be included because isAllowedToCloneForAutoscale is NOT NULL
        self::assertCount(0, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_allowed_clone'
        ));
    }

    /**
     * Testing: server.isProvisioningEnabled = 1
     */
    public function testCleanupUnusedProvisionedServers_excludesServerWithProvisioningDisabled(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_prov_disabled')
            ->setUrl('url_prov_disabled')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(false) // Provisioning disabled, should be excluded
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_prov_disabled')
            ->setSlug('slug_prov_disabled')
            ->setUid('uid_prov_disabled')
            ->setUidReal('uid_real_prov_disabled')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should NOT be included because isProvisioningEnabled is false
        self::assertCount(0, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_prov_disabled'
        ));
    }

    /**
     * Testing: room.persistantRoom = true (persistent room should be eligible for cleanup)
     */
    public function testCleanupUnusedProvisionedServers_includesPersistentRoom(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_persistent')
            ->setUrl('url_persistent')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_persistent')
            ->setSlug('slug_persistent')
            ->setUid('uid_persistent')
            ->setUidReal('uid_real_persistent')
            ->setPersistantRoom(true) // Persistent room
            ->setDuration(60)
            ->setSequence(0)
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Persistent room should be included
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_persistent'
        ));
    }

    /**
     * Testing: room.persistantRoom = false AND room.endDateUtc < :now (non-persistent room with past end date)
     */
    public function testCleanupUnusedProvisionedServers_includesNonPersistentRoomWithPastEndDate(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_past_end')
            ->setUrl('url_past_end')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_past_end')
            ->setSlug('slug_past_end')
            ->setUid('uid_past_end')
            ->setUidReal('uid_real_past_end')
            ->setPersistantRoom(false)
            ->setEndDateUtc(new \DateTime('-1 hour', new \DateTimeZone('utc'))) // Past end date
            ->setDuration(60)
            ->setSequence(0)
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Non-persistent room with past end date should be included
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_past_end'
        ));
    }

    /**
     * Testing: room.persistantRoom = false AND room.endDateUtc >= :now (non-persistent room with future end date should be excluded)
     */
    public function testCleanupUnusedProvisionedServers_excludesNonPersistentRoomWithFutureEndDate(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_future_end')
            ->setUrl('url_future_end')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_future_end')
            ->setSlug('slug_future_end')
            ->setUid('uid_future_end')
            ->setUidReal('uid_real_future_end')
            ->setPersistantRoom(false)
            ->setEndDateUtc(new \DateTime('+1 hour', new \DateTimeZone('utc'))) // Future end date
            ->setDuration(60)
            ->setSequence(0)
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Non-persistent room with future end date should NOT be included
        self::assertCount(0, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_future_end'
        ));
    }

    /**
     * Testing: status_participant.inRoom = true (participant still in room should be excluded)
     */
    public function testCleanupUnusedProvisionedServers_excludesRoomWithParticipantInRoom(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_participant_in')
            ->setUrl('url_participant_in')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_participant_in')
            ->setSlug('slug_participant_in')
            ->setUid('uid_participant_in')
            ->setUidReal('uid_real_participant_in')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $roomStatus = (new RoomStatus())
            ->setRoom($room)
            ->setCreated(true)
            ->setDestroyed(false)
            ->setJitsiRoomId('jitsi_room_id_participant_in')
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
        ;
        $participant = (new RoomStatusParticipant())
            ->setRoomStatus($roomStatus)
            ->setInRoom(true) // Participant is IN the room
            ->setParticipantId('participant_id')
            ->setParticipantName('Test Participant')
            ->setEnteredRoomAt(new \DateTime())
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $this->persistAndTrack($entityManager, $roomStatus);
        $this->persistAndTrack($entityManager, $participant);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should NOT be included because participant is still in room
        self::assertCount(0, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_participant_in'
        ));
    }

    /**
     * Testing: status_participant.inRoom = false (participant left room should be included)
     */
    public function testCleanupUnusedProvisionedServers_includesRoomWithParticipantLeftRoom(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_participant_left')
            ->setUrl('url_participant_left')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_participant_left')
            ->setSlug('slug_participant_left')
            ->setUid('uid_participant_left')
            ->setUidReal('uid_real_participant_left')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $roomStatus = (new RoomStatus())
            ->setRoom($room)
            ->setCreated(true)
            ->setDestroyed(false)
            ->setJitsiRoomId('jitsi_room_id_participant_left')
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
        ;
        $participant = (new RoomStatusParticipant())
            ->setRoomStatus($roomStatus)
            ->setInRoom(false) // Participant LEFT the room
            ->setParticipantId('participant_id')
            ->setParticipantName('Test Participant')
            ->setEnteredRoomAt(new \DateTime())
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $this->persistAndTrack($entityManager, $roomStatus);
        $this->persistAndTrack($entityManager, $participant);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should be included because participant left the room
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_participant_left'
        ));
    }

    /**
     * Testing: status.destroyed = 1 (destroyed status should be included even with participant)
     */
    public function testCleanupUnusedProvisionedServers_includesRoomWithDestroyedStatus(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_destroyed')
            ->setUrl('url_destroyed')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_destroyed')
            ->setSlug('slug_destroyed')
            ->setUid('uid_destroyed')
            ->setUidReal('uid_real_destroyed')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $roomStatus = (new RoomStatus())
            ->setRoom($room)
            ->setCreated(true)
            ->setDestroyed(true) // Status is DESTROYED
            ->setJitsiRoomId('jitsi_room_id_destroyed')
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
        ;
        $participant = (new RoomStatusParticipant())
            ->setRoomStatus($roomStatus)
            ->setInRoom(true) // Participant would be in room, but status is destroyed
            ->setParticipantId('participant_id')
            ->setParticipantName('Test Participant')
            ->setEnteredRoomAt(new \DateTime())
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $this->persistAndTrack($entityManager, $roomStatus);
        $this->persistAndTrack($entityManager, $participant);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should be included because status is destroyed
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_destroyed'
        ));
    }

    /**
     * Testing: recording.id IS NULL (no recording should be included)
     */
    public function testCleanupUnusedProvisionedServers_includesRoomWithNoRecording(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_no_recording')
            ->setUrl('url_no_recording')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_no_recording')
            ->setSlug('slug_no_recording')
            ->setUid('uid_no_recording')
            ->setUidReal('uid_real_no_recording')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
            // No recording attached
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should be included because there is no recording
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_no_recording'
        ));
    }

    /**
     * Testing: recording.user IS NULL (recording without user should be included)
     */
    public function testCleanupUnusedProvisionedServers_includesRoomWithRecordingWithoutUser(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_recording_no_user')
            ->setUrl('url_recording_no_user')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_recording_no_user')
            ->setSlug('slug_recording_no_user')
            ->setUid('uid_recording_no_user')
            ->setUidReal('uid_real_recording_no_user')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $recording = (new Recording())
            ->setRoom($room)
            ->setUid('recording_uid_no_user')
            ->setUser(null) // Recording without user
            ->setCreatedAt(new \DateTimeImmutable())
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $this->persistAndTrack($entityManager, $recording);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should be included because recording has no user
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_recording_no_user'
        ));
    }

    /**
     * Testing: recording.user IS NOT NULL (active recording with user should be excluded)
     */
    public function testCleanupUnusedProvisionedServers_excludesRoomWithActiveRecording(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);

        // Get an existing user from fixtures
        $existingRoom = $roomsRepository->findOneBy([]);
        $existingUser = $existingRoom?->getModerator();

        if ($existingUser === null) {
            self::markTestSkipped('No user found in fixtures to test with');
        }

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_active_recording')
            ->setUrl('url_active_recording')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_active_recording')
            ->setSlug('slug_active_recording')
            ->setUid('uid_active_recording')
            ->setUidReal('uid_real_active_recording')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $recording = (new Recording())
            ->setRoom($room)
            ->setUid('recording_uid_active')
            ->setUser($existingUser) // Active recording with user
            ->setCreatedAt(new \DateTimeImmutable())
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $this->persistAndTrack($entityManager, $recording);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should NOT be included because there is an active recording with a user
        self::assertCount(0, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_active_recording'
        ));
    }

    /**
     * Testing: status_participant.inRoom IS NULL (no participant record should be included)
     */
    public function testCleanupUnusedProvisionedServers_includesRoomWithNoParticipantRecords(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_no_participants')
            ->setUrl('url_no_participants')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)
            ->setAllowedToCloneForAutoscale(null)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_no_participants')
            ->setSlug('slug_no_participants')
            ->setUid('uid_no_participants')
            ->setUidReal('uid_real_no_participants')
            ->setPersistantRoom(true)
            ->setDuration(60)
            ->setSequence(0)
        ;
        $roomStatus = (new RoomStatus())
            ->setRoom($room)
            ->setCreated(true)
            ->setDestroyed(false)
            ->setJitsiRoomId('jitsi_room_id_no_participants')
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            // No participants added
        ;
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $this->persistAndTrack($entityManager, $roomStatus);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should be included because there are no participant records
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_no_participants'
        ));
    }

    /**
     * Testing: Complete scenario - all conditions met for cleanup
     */
    public function testCleanupUnusedProvisionedServers_includesRoomWithAllConditionsMet(): void
    {
        self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.provisioner_request');
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $server = (new Server())
            ->setServerName('server_name')
            ->setSlug('slug_all_conditions')
            ->setUrl('url_all_conditions')
            ->setAppId('app_id')
            ->setAppSecret('app_secret')
            ->setJwtModeratorPosition(1)
            ->setIsProvisioningEnabled(true)      // Provisioning enabled
            ->setAllowedToCloneForAutoscale(null) // Not allowed to clone (is a provisioned server)
        ;
        $room = (new Rooms())
            ->setServer($server)
            ->setName('room_all_conditions')
            ->setSlug('slug_all_conditions')
            ->setUid('uid_all_conditions')
            ->setUidReal('uid_real_all_conditions')
            ->setPersistantRoom(false)
            ->setEndDateUtc(new \DateTime('-1 hour', new \DateTimeZone('utc'))) // Past end date
            ->setDuration(60)
            ->setSequence(0)
        ;
        $roomStatus = (new RoomStatus())
            ->setRoom($room)
            ->setCreated(true)
            ->setDestroyed(false)
            ->setJitsiRoomId('jitsi_room_id_all_conditions')
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
        ;
        $participant = (new RoomStatusParticipant())
            ->setRoomStatus($roomStatus)
            ->setInRoom(false)
            ->setParticipantId('participant_id')
            ->setParticipantName('Test Participant')
            ->setEnteredRoomAt(new \DateTime())
        ;
        // No recording
        $this->persistAndTrack($entityManager, $server);
        $this->persistAndTrack($entityManager, $room);
        $this->persistAndTrack($entityManager, $roomStatus);
        $this->persistAndTrack($entityManager, $participant);
        $entityManager->flush();

        $countUnused = $provisionerService->cleanupUnusedProvisionedServers();
        $sent = $transport->getSent();

        // Room should be included - all conditions are met
        self::assertCount(1, array_filter($sent, fn($envelope) =>
            $envelope->getMessage()->room_id === 'uid_real_all_conditions'
        ));
    }
}
