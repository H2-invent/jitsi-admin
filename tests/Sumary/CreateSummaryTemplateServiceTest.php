<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Service\Summary\CreateSummaryService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CreateSummaryTemplateServiceTest extends KernelTestCase
{
    private static string $sampleSvgResult = '';

    public static function setUpBeforeClass(): void
    {
        self::$sampleSvgResult = '<div class="page_break"></div><img src="data:image/svg+xml;base64,'
            . base64_encode(CreateWhiteboardServiceTest::$sampleSvg)
            . '" style="width: 600px"/>';
    }

    public function testWhiteboardSuccess(): void
    {
        $kernel = self::bootKernel();

        $responses = [
            new MockResponse(CreateWhiteboardServiceTest::$sampleSvg, ['http_code' => 200]),
            new MockResponse(CreateEtherpadServiceTest::$samplePadHtml, ['http_code' => 200]),
        ];

        $httpClient = new MockHttpClient($responses);

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(CreateSummaryService::class);
        $service->setHttpClient($httpClient);

        $summary = $service->createSummary($room);

        $normalize = static fn (string $value): string => trim(preg_replace('~[\r\n\s]+~', '', $value));
        $normalizedSummary = $normalize($summary);

        // Valid, self-contained HTML document.
        self::assertStringStartsWith('<!DOCTYPE', ltrim($summary));
        self::assertStringContainsString('</html>', $summary);
        self::assertStringContainsString('<title>TestMeeting: 0</title>', $summary);

        // Header information (title, participants) is present.
        self::assertStringContainsString('<h1>TestMeeting: 0</h1>', $summary);
        self::assertStringContainsString('test@local3.de', $summary);

        // Whiteboard and etherpad exports are embedded.
        self::assertStringContainsString($normalize(self::$sampleSvgResult), $normalizedSummary);
        self::assertStringContainsString($normalize(CreateEtherpadServiceTest::$samplePadREsult), $normalizedSummary);

        $this->assertSame('test', $kernel->getEnvironment());
    }
}
