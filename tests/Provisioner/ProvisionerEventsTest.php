<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Controller\api\ApiMiddlewareController;
use App\Controller\ProvisionerController;
use App\Entity\Rooms;
use App\Message\Provisioner\Enum\Status;
use App\Message\Provisioner\Enum\Type;
use App\Message\Provisioner\ProvisionerRequestMessage;
use App\Message\Provisioner\ProvisionerStatusMessage;
use App\MessageHandler\ProvisionerStatusMessageHandler;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use App\Tests\Functional\Fixtures\HubStub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class ProvisionerEventsTest extends KernelTestCase
{
    public function test_MeetingStarts_shouldRequestProvisioning(): void
    {
        /** @var ProvisionerController $controller */
        $controller = self::getContainer()->get(ProvisionerController::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy([
            'isProvisioningEnabled' => true,
            'isAllowedToCloneForAutoscale' => true,
        ]);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['server' => $server], ['id' => 'ASC']);
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
        $controller = self::getContainer()->get(ApiMiddlewareController::class);
        $server = self::getContainer()->get(ServerRepository::class)->findOneBy(['isProvisioningEnabled' => true]);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy([]);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy([]);
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
        /** @var ProvisionerStatusMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get(ProvisionerStatusMessageHandler::class);
        /** @var InMemoryTransport $messageTransport */
        $messageTransport = self::getContainer()->get('messenger.transport.provisioner_status');
        /** @var HubStub $mercureHub */
        $mercureHub = self::getContainer()->get(HubStub::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy([]);
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
        /** @var ProvisionerStatusMessageHandler $messageHandler */
        $messageHandler = self::getContainer()->get(ProvisionerStatusMessageHandler::class);
        /** @var InMemoryTransport $messageTransport */
        $messageTransport = self::getContainer()->get('messenger.transport.provisioner_request');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy([]);
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
