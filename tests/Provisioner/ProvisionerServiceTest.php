<?php

namespace App\Tests\Provisioner;

use App\Repository\RoomsRepository;
use App\Service\ProvisionerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

class ProvisionerServiceTest extends KernelTestCase
{
    public function testProvisionNewInstance_sendsMessage(): void
    {
        $kernel = self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);
        /** @var InMemoryTransport $transport */
        $transport = self::getContainer()->get('messenger.transport.rabbitmq');

        $room = $roomsRepository->findOneBy([]);
        $provisionerService->provisionNewServerForRoom($room);

        $sentMessages = $transport->getSent();
        $this->assertCount(1, $sentMessages);
    }

    public function testProvisionNewInstance_savesOriginalServerId(): void
    {
        $kernel = self::bootKernel();
        /** @var ProvisionerService $provisionerService */
        $provisionerService = self::getContainer()->get(ProvisionerService::class);
        /** @var RoomsRepository $roomsRepository */
        $roomsRepository = self::getContainer()->get(RoomsRepository::class);

        $room = $roomsRepository->findOneBy([]);
        $provisionerService->provisionNewServerForRoom($room);

        $this->assertSame($room->getServer(), $room->getOriginalServer());
    }
}
