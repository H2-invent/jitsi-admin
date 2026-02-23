<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Message\ProvisionerStatus\Status;
use App\Message\ProvisionerStatusMessage;
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
            Status::READY,
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
                self::assertSame('redirect', $updateData['type'] ?? null);
                self::assertContains(ProvisionerService::WEBSOCKET_TOPIC_NAME . $room->getUidReal(), $update->getTopics());
                return 'id';
            }
        );
        $directSend->setMercurePublisher($hub);

        $randomString = uniqid();
        $statusMessage = new ProvisionerStatusMessage(
            $randomString,
            Status::READY,
            $randomString,
            $randomString,
            $randomString,
            $randomString,
        );
        $provisionerService = new ProvisionerService($directSend, $messageBus, $urlGenerator, $entityManager, $serverService);
        $provisionerService->saveNewServerAndRedirect($room, $statusMessage);
    }
}
