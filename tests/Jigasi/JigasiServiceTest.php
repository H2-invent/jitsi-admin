<?php

namespace App\Tests\Jigasi;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Service\Jigasi\JigasiService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class JigasiServiceTest extends KernelTestCase
{
    public function testGetNumber(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());


        $jigasiService = self::getContainer()->get(JigasiService::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->getServer()->setJigasiNumberUrl(
            '{
    "message": "Einwahlnummern",
    "numbers": {
        "DE": [
            "0123456789"
        ],
         "FR": [
            "1234560123456789"
        ]
    },
    "numbersEnabled": true
}'
        )

        ->setJigasiApiUrl('https://jigasi.org/conferenceMapper')
        ->setJigasiProsodyDomain('conference.jigasi.org');
        $res = $jigasiService->getNumber($room);
        self::assertNotNull($res);
        self::assertNotNull($res['FR']);
        self::assertNotNull($res['DE']);
        self::assertEquals("0123456789", $res['DE'][0]);
        self::assertEquals("1234560123456789", $res['FR'][0]);

        $room->getServer()->setJigasiNumberUrl('https://invalid.url');
        self::assertNull($jigasiService->getNumber($room));

        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $room->setServer($server);
        $res = $jigasiService->getNumber($room);
        self::assertNull($res);

        $res = $jigasiService->getNumber(null);
        self::assertNull($res);
    }

    public function testGetPIN(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());


        $callback = function ($method, $url, $options) {
            if ($url === 'https://jigasi.org/conferenceMapper?conference=123456780@conference.jigasi.org&url=https://meet.jit.si2/123456780') {
                return new MockResponse(
                    '
                {
    "conference": "test@conference.domain",
    "id": 154428,
    "message": "Successfully retrieved conference mapping"
}'
                );
            } else {
                return new MockResponse('', ['http_code' => 404]);
            }
        };


        $jigasiService = self::getContainer()->get(JigasiService::class);
        $jigasiService->setClient(new MockHttpClient($callback));
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->getServer()->setJigasiNumberUrl(
            '{
    "message": "Einwahlnummern",
    "numbers": {
        "DE": [
            "0123456789"
        ],
         "FR": [
            "1234560123456789"
        ]
    },
    "numbersEnabled": true
}'
        )
            ->setJigasiApiUrl('https://jigasi.org/conferenceMapper')
            ->setJigasiProsodyDomain('conference.jigasi.org');

        $res = $jigasiService->getRoomPin($room);
        self::assertEquals("154428", $res);

        $room->getServer()->setJigasiApiUrl('https://invalid.url');
        self::assertNull($jigasiService->getRoomPin($room));

        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $room->setServer($server);

        $res = $jigasiService->getRoomPin($room);
        self::assertNull($res);

        $res = $jigasiService->getRoomPin(null);
        self::assertNull($res);
    }
}
