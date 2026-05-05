<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Service\Summary\CreateSummaryService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CreateSummaryTemplateServiceTest extends KernelTestCase
{
    private $sampleSvgResult = '';
    public static $pdfHeader = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>TestMeeting: 0</title>
    <style>
        @font-face {
            font-family: \'Poppins\';
            font-style: normal;
            font-weight: normal;
            src: url("fonts/poppins-v9-latin-regular.ttf") format(\'truetype\');
        }
        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-Black.ttf\') format(\'truetype\');
            font-weight: 900;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-Bold.ttf\') format(\'truetype\');
            font-weight: bold;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-BlackItalic.ttf\') format(\'truetype\');
            font-weight: 900;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-BoldItalic.ttf\') format(\'truetype\');
            font-weight: bold;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto Condensed\';
            src: url(\'fonts/RobotoCondensed-LightItalic.ttf\') format(\'truetype\');
            font-weight: 300;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto Condensed\';
            src: url(\'fonts/RobotoCondensed-Light.ttf\') format(\'truetype\');
            font-weight: 300;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto Condensed\';
            src: url(\'fonts/RobotoCondensed-Italic.ttf\') format(\'truetype\');
            font-weight: normal;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto Condensed\';
            src: url(\'fonts/RobotoCondensed-Regular.ttf\') format(\'truetype\');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto Condensed\';
            src: url(\'fonts/RobotoCondensed-Bold.ttf\') format(\'truetype\');
            font-weight: bold;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto Condensed\';
            src: url(\'fonts/RobotoCondensed-BoldItalic.ttf\') format(\'truetype\');
            font-weight: bold;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-Italic.ttf\') format(\'truetype\');
            font-weight: normal;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-LightItalic.ttf\') format(\'truetype\');
            font-weight: 300;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-Light.ttf\') format(\'truetype\');
            font-weight: 300;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-MediumItalic.ttf\') format(\'truetype\');
            font-weight: 500;
            font-style: italic;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-Medium.ttf\') format(\'truetype\');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-Regular.ttf\') format(\'truetype\');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-Thin.ttf\') format(\'truetype\');
            font-weight: 100;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: \'Roboto\';
            src: url(\'fonts/Roboto-ThinItalic.ttf\') format(\'truetype\');
            font-weight: 100;
            font-style: italic;
            font-display: swap;
        }


        .page_break { page-break-before: always; }
        body{
            font-family: Roboto;
        }
        table td {
            padding: 16px;
        }
        h1, h2, h3, h4{
        margin:0;padding:0;
            font-family: Roboto;
        }

    </style>
</head>
<body>
';

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->sampleSvgResult = '<div class="page_break"></div><img src="data:image/svg+xml;base64,' . (base64_encode(CreateWhiteboardServiceTest::$sampleSvg)) . '"  style="width: 600px"/>';
    }

    public function testWhiteboardSuccess(): void
    {
        $kernel = self::bootKernel();
        // Arrange


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

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(CreateSummaryService::class);
        $service->setHttpClient($httpClient);
        $whiteboardResponse = $service->createSummary($room);


        self::assertSame(
            trim(preg_replace('~[\r\n\s]+~', '', $whiteboardResponse)),
            trim(
                preg_replace(
                    '~[\r\n\s]+~',
                    '',
                    self::$pdfHeader .
                    sprintf(CreateHeaderServiceTest::$headerHtml, $room->getStart()->format('d.m.Y'), $room->getStart()->format('H:i'), $room->getEnddate()->format('H:i')) .
                    $this->sampleSvgResult .
                    CreateEtherpadServiceTest::$samplePadREsult .
                    '
</body></html>'
                )
            )
        );

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
}
