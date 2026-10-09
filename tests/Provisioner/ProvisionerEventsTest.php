<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Controller\api\ApiMiddlewareController;
use App\Controller\ProvisionerController;
use App\Message\Provisioner\Enum\Status;
use App\Message\Provisioner\Enum\Type;
use App\Message\Provisioner\ProvisionerRequestMessage;
use App\Message\Provisioner\ProvisionerStatusMessage;
use App\MessageHandler\ProvisionerStatusMessageHandler;
use App\Service\RoomService;
use App\Tests\Builder\RoomsBuilder;
use App\Tests\Builder\ServerBuilder;
use App\Tests\Builder\UserBuilder;
use App\Tests\Functional\Fixtures\HubStub;
use App\Tests\TransactionTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class ProvisionerEventsTest extends KernelTestCase
{
    use TransactionTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->beginTransaction();
    }

    public function test_MeetingStarts_shouldRequestProvisioning(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = UserBuilder::create()->persist($entityManager);
        $server = ServerBuilder::create()
            ->withAdministrator($user)
            ->withUser($user)
            ->withProvisioning(true, true)
            ->persist($entityManager);
        $room = RoomsBuilder::create($server)
            ->withModerator($user)
            ->withCreator($user)
            ->withParticipant($user)
            ->persist($entityManager);

        /** @var ProvisionerController $controller */
        $controller = self::getContainer()->get(ProvisionerController::class);
        /** @var InMemoryTransport $messageTransport */
        $messageTransport = self::getContainer()->get('messenger.transport.provisioner_request');

        $controller->create($room);

        $sentMessages = $messageTransport->getSent();
        self::assertCount(1, $sentMessages);
        foreach ($sentMessages as $sentMessage) {
            /** @var ProvisionerRequestMessage $message */
            $message = $sentMessage->getMessage();
            self::assertSame(Type::PROVISION, $message->type);
            self::assertSame($room->getUidReal(), $message->room_id);
        }
    }

    public function test_MeetingEnds_shouldRequestDeletion(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = UserBuilder::create()->persist($entityManager);
        $server = ServerBuilder::create()
            ->withAdministrator($user)
            ->withUser($user)
            ->withProvisioning(true, true)
            ->persist($entityManager);
        $room = RoomsBuilder::create($server)
            ->withModerator($user)
            ->withCreator($user)
            ->withParticipant($user)
            ->persist($entityManager);

        $controller = self::getContainer()->get(ApiMiddlewareController::class);
        $roomService = self::getContainer()->get(RoomService::class);
        $jwt = $roomService->generateJwt($room, $user, 'user_name');
        $request = new Request([
            'host' => $server->getUrl(),
            'key' => $server->getAppId(),
            'jwt' => $jwt,
        ]);
        /** @var InMemoryTransport $messageTransport */
        $messageTransport = self::getContainer()->get('messenger.transport.provisioner_request');

        $controller->roomDeleted($request);

        $sentMessages = $messageTransport->getSent();
        $this->assertCount(1, $sentMessages);
        foreach ($sentMessages as $sentMessage) {
            /** @var ProvisionerRequestMessage $message */
            $message = $sentMessage->getMessage();
            self::assertSame(Type::DELETION, $message->type);
            self::assertSame($room->getUidReal(), $message->room_id);
        }
    }

    public function test_ProvisioningDone_shouldRedirectToMeeting(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = UserBuilder::create()->persist($entityManager);
        $server = ServerBuilder::create()
            ->withAdministrator($user)
            ->withUser($user)
            ->withProvisioning(true, true)
            ->persist($entityManager);
        $room = RoomsBuilder::create($server)
            ->withModerator($user)
            ->withCreator($user)
            ->withParticipant($user)
            ->persist($entityManager);

        /** @var ProvisionerStatusMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get(ProvisionerStatusMessageHandler::class);
        /** @var HubStub $mercureHub */
        $mercureHub = self::getContainer()->get(HubStub::class);
        $doneMessage = new ProvisionerStatusMessage(
            $room->getUidReal(),
            Type::PROVISION,
            Status::DONE,
            'name',
            'app_id',
            'app_secret',
            'url',
        );

        $messageHandler($doneMessage);

        $updates = $mercureHub->getUpdates();
        self::assertCount(1, $updates);
        foreach ($updates as $update) {
            $data = json_decode($update->getData(), true, flags: JSON_THROW_ON_ERROR);
            self::assertArrayHasKey('type', $data);
            self::assertStringStartsWith('redirect', $data['type']);
        }
    }

    public function test_ProvisioningFailed_shouldRetry(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = UserBuilder::create()->persist($entityManager);
        $server = ServerBuilder::create()
            ->withAdministrator($user)
            ->withUser($user)
            ->withProvisioning(true, true)
            ->persist($entityManager);
        $room = RoomsBuilder::create($server)
            ->withModerator($user)
            ->withCreator($user)
            ->withParticipant($user)
            ->persist($entityManager);

        /** @var ProvisionerStatusMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get(ProvisionerStatusMessageHandler::class);
        /** @var InMemoryTransport $messageTransport */
        $messageTransport = self::getContainer()->get('messenger.transport.provisioner_request');
        $failedMessage = new ProvisionerStatusMessage($room->getUidReal(), Type::PROVISION, Status::FAILED);

        $messageHandler($failedMessage);

        $sentMessages = $messageTransport->getSent();
        self::assertCount(1, $sentMessages);
        foreach ($sentMessages as $sentMessage) {
            /** @var ProvisionerRequestMessage $message */
            $message = $sentMessage->getMessage();
            self::assertSame(Type::PROVISION, $message->type);
            self::assertSame($room->getUidReal(), $message->room_id);
        }
    }
}
