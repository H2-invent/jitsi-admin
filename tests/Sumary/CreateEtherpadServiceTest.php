<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Service\Summary\CreateSummaryService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CreateEtherpadServiceTest extends KernelTestCase
{
    public static $samplePadREsult = '<div class="page_break"></div><h1>test</h1>';
    public static $samplePadHtml = '<h1>test</h1>';


    public function testEtherpadSuccess(): void
    {
        $kernel = self::bootKernel();


        $mockResponse = new MockResponse(
            self::$samplePadHtml,
            [
                'http_code' => 200,
            ]
        );

        $httpClient = new MockHttpClient($mockResponse, 'http://etherpadurl.com');

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(CreateSummaryService::class);
        $service->setHttpClient($httpClient);
        $whiteboardResponse = $service->createEtherpadExport($room);


        self::assertSame('GET', $mockResponse->getRequestMethod());

        self::assertEquals('http://etherpadurl.com/p/' . $room->getUidReal() . '/export/html', $mockResponse->getRequestUrl());

        self::assertSame($whiteboardResponse, self::$samplePadREsult);
    }
    public function testEtherpadnotfound(): void
    {
        $kernel = self::bootKernel();
        // Arrange


        $mockResponse = new MockResponse(
            'kjsdhfkjfhds',
            [
                'http_code' => 404,
            ]
        );

        $httpClient = new MockHttpClient($mockResponse, 'http://whiteboardurl.com');

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(CreateSummaryService::class);
        $service->setHttpClient($httpClient);
        $whiteboardResponse = $service->createEtherpadExport($room);


        self::assertSame('GET', $mockResponse->getRequestMethod());

        self::assertStringStartsWith('http://etherpadurl.com/p/' . $room->getUidReal() . '/export/html', $mockResponse->getRequestUrl());

        self::assertSame($whiteboardResponse, '');
    }
}
