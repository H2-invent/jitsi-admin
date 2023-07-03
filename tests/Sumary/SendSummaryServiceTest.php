<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Service\Summary\CreateSummaryService;
use App\Service\Summary\SendSummaryViaEmailService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class SendSummaryServiceTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();
//prepare
        $responses = [
            new MockResponse(
                CreateWhiteboardServiceTest::$sampleSvg,
                [
                    'http_code' => 200,
                ]
            ),
            new MockResponse(
                CreateEtherpadServiceTest::$samplePadHtml,
                [
                    'http_code' => 200,
                ]
            )
        ];

        $httpClient = new MockHttpClient($responses);

        $service1 = self::getContainer()->get(CreateSummaryService::class);
        $service1->setHttpClient($httpClient);

        $this->assertSame('test', $kernel->getEnvironment());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(SendSummaryViaEmailService::class);
        $service->sendSummaryForRoom($room);
        $this->assertEmailCount(3);

        $email = $this->getMailerMessage();

        $this->assertEmailHtmlBodyContains($email, 'Konferenz Abgeschlossen');
        $this->assertEmailHtmlBodyContains($email, $room->getName());
        self::assertEmailAttachmentCount($email, 1);
    }
}
