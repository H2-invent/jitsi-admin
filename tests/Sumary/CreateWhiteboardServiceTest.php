<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Service\Summary\CreateSummaryService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CreateWhiteboardServiceTest extends KernelTestCase
{
    private $sampleSvgResult = '';
    public static $sampleSvg = '<svg id="canvas" width="3109" height="2590" version="1.1" xmlns="http://www.w3.org/2000/svg">
			<defs id="defs"><pattern id="smallGrid" width="30" height="30" patternUnits="userSpaceOnUse"><path d="M 30 0 L 0 0 0 30" fill="none" stroke="gray" stroke-width="0.5"></path></pattern><pattern id="grid" width="300" height="300" patternUnits="userSpaceOnUse"><rect width="300" height="300" fill="url(#smallGrid)"></rect><path d="M 300 0 L 0 0 0 300" fill="none" stroke="gray" stroke-width="1"></path></pattern><pattern id="dots" width="30" height="30" x="-10" y="-10" patternUnits="userSpaceOnUse"><circle fill="gray" cx="10" cy="10" r="2"></circle></pattern></defs>
			<rect id="gridContainer" width="100%" height="100%" fill="none"></rect><g id="drawingArea"><path id="ll9mkjwdd2" stroke="#aaaaaa" stroke-width="4" opacity="1" d="M 356 79 L 356 79 C 356 79 349.3478888069929 98.73728019371018 349 109 C 348.6676724883432 118.80366159387492 349.61315096630346 129.22630193260687 354 138 C 360.2627647426853 150.5255294853706 368.21184465306726 163.44020472316268 380 171 C 399.9041580609656 183.7646231043149 424.09583021429137 188.09390898822835 446 197 C 454.4338504282137 200.42914797630667 465.9498244085702 200.42473661285527 471 208 C 473.92352673102346 212.38529009653516 464.735254933381 216.4948742273725 462 221 C 459.05912390596336 225.8437959195898 455.1999356330329 230.46183553984815 454 236 C 450.80415314762126 250.75006239559409 448.10056813536437 265.9345162673529 449 281 C 449.47218867079926 288.90916023588767 452.7132294850264 297.0984887274713 458 303 C 467.5198251099134 313.6267815180429 481.07546686473637 319.82339216637854 492 329 C 497.77530775765615 333.85125851643113 503.98127065492 338.61731221663774 508 345 C 509.9617049728105 348.11564907446365 508.0513410013053 352.4425287548947 509 356 C 510.73909034371576 362.521588788934 512.326989984945 369.3374428934569 516 375 C 520.4911795875253 381.9239018641014 525.8035652533063 388.9598962825579 533 393 C 545.3180034306946 399.91537034705664 560.3649517434857 400.68247587174284 573 407 C 576.2249030993194 408.6124515496597 577.2111456180002 412.8695048315003 579 416 C 581.218800784901 419.8829013735766 582.6171866193357 424.21553168953324 585 428 C 588.3036332390324 433.2469469090515 592.4873182470435 437.8906447229723 596 443 C 596.1888429409623 443.2746806413998 596 444 596 444"></path><text id="tl9mkjywn7" x="472" y="176" font-size="36" fill="#aaaaaa" opacity="1">Test</text><line id="sl9mkk1jqm" stroke="#aaaaaa" stroke-width="4" opacity="1" y2="123" x1="540" x2="676" y1="346"></line><line id="sl9mkk21iv" stroke="#aaaaaa" stroke-width="4" opacity="1" y2="134" x1="469" x2="785" y1="82"></line><line id="sl9mkk4cil" stroke="#0074d9" stroke-width="4" opacity="1" y2="302" x1="243" x2="731" y1="397"></line><line id="sl9mkk4za1" stroke="#0074d9" stroke-width="4" opacity="1" y2="420" x1="539" x2="661" y1="53"></line></g>
			<g id="cursors"><circle class="opcursor" id="cursor-me" cx="0" cy="0" r="2" fill="#0074d9" style="transform: translate(63px, 82px);"></circle></g>
		<rect id="selectionRect" stroke="black" stroke-width="1" vector-effect="non-scaling-stroke" fill="none" stroke-dasharray="5 5" opacity="1" width="0" y="0" x="0" height="0" style="display: none;"></rect><image href="tools/hand/delete.svg" width="24" height="24" style="display: none;"></image><image href="tools/hand/duplicate.svg" width="24" height="24" style="display: none;"></image><image href="tools/hand/handle.svg" width="14" height="14" style="display: none;"></image><style>html, body, svg { padding: 0px; margin: 0px; font-family: "Liberation sans", sans-serif; },#canvas { transform-origin: 0px 0px; },#loadingMessage { font-size: 1.5em; background: linear-gradient(rgb(238, 238, 238), rgb(204, 204, 204)) rgb(238, 238, 238); padding: 20px; width: 40%; line-height: 50px; text-align: center; border-radius: 10px; position: fixed; top: 40%; left: 30%; z-index: 1; box-shadow: rgb(51, 51, 51) 0px 0px 2px; transition: all 1s ease 0s; },#loadingMessage.hidden { display: none; opacity: 0; z-index: -1; },#loadingMessage::after { content: "..."; },#menu::-webkit-scrollbar { display: none; },#menu { font-size: 16px; border-radius: 0px; overflow-y: scroll; position: fixed; margin-bottom: 30px; left: 0px; top: 0px; color: black; max-height: 100%; transition-duration: 1s; cursor: default; padding: 10px; },#menu.closed { border-radius: 3px; left: 10px; top: 10px; background-color: rgba(100, 200, 255, 0.7); width: 6vw; height: 2em; transition-duration: 1s; },#menu h2 { display: none; font-size: 4vh; text-align: center; letter-spacing: 0.5vw; text-shadow: white 0px 0px 5px; color: black; padding: 0px; margin: 0px; },#menu .tools { list-style-type: none; padding: 0px; },#settings { margin-bottom: 20px; },#menu .tool { position: relative; user-select: none; pointer-events: auto; white-space: nowrap; list-style-position: inside; border: 1px solid rgb(238, 238, 238); text-decoration: none; cursor: pointer; background: rgb(255, 255, 255); margin-top: 10px; height: 40px; line-height: 40px; border-radius: 0px; max-width: 40px; transition-duration: 0.2s; overflow: hidden; width: max-content; box-shadow: rgb(143, 162, 188) 0px 0px 3px inset; },#menu .tool:hover { max-width: 100%; },@media (hover: none), (pointer: coarse) {
  #menu .tool:hover { max-width: 40px; }
  #menu .tool:focus { max-width: 100%; }
  #menu { pointer-events: auto; }
  #menu:focus-within { pointer-events: none; }
},#menu .oneTouch:active { border-radius: 3px; background-color: rgb(238, 238, 255); },#menu .tool:active { box-shadow: rgb(221, 238, 255) 0px 0px 1px inset; background-color: rgb(238, 238, 255); },#menu .tool.curTool { box-shadow: rgb(0, 116, 217) 0px 0px 5px; background: linear-gradient(rgb(150, 225, 255), rgb(54, 162, 255)); },#menu .tool-icon { display: inline-block; text-align: center; width: 35px; height: 35px; margin: 2.5px; font-family: mono, monospace; overflow: hidden; },#menu img.tool-icon { pointer-events: none; },#menu .tool-icon > * { display: block; margin: auto; },#menu .tool-name { text-align: center; font-size: 23px; margin-right: 20px; margin-left: 20px; margin-bottom: 2.5px; display: inline-block; vertical-align: text-bottom; },#menu .tool-name.slider { display: inline-block; width: 150px; height: 30px; font-size: 0.9em; line-height: 15px; vertical-align: top; padding: 6px; },#menu .tool.hasSecondary .tool-icon { margin-top: 0px; margin-left: 0px; },#menu .tool .tool-icon.secondaryIcon { display: none; },#menu .tool.hasSecondary .tool-icon.secondaryIcon { display: block; position: absolute; bottom: 0px; left: 26px; width: 12px; height: 12px; },input { font-size: 16px; },#chooseColor { width: 100%; height: 100%; border: 0px; border-radius: 0px; color: black; display: block; margin: 0px; padding: 0px; },.colorPresets { margin-right: 20px; vertical-align: top; display: inline-block; },.colorPresetButton { width: 30px; height: 30px; border: 1px solid black; border-radius: 3px; display: inline-block; margin-right: 6px; padding: 0px; vertical-align: middle; },.rangeChooser { display: block; border: 0px; width: 100%; margin: 0px; background: transparent; },line { fill: none; stroke-linecap: round; stroke-linejoin: round; },path { fill: none; stroke-linecap: round; stroke-linejoin: round; },text { font-family: Arial, Helvetica, sans-serif; user-select: none; },circle.opcursor { pointer-events: none; transition: all 0.1s ease 0s; },#cursor-me { transition: all 0s ease 0s; },@media screen and (-ms-high-contrast: active), (-ms-high-contrast: none) {
  #chooseColor { color: transparent; }
  label.tool-name[for="chooseColor"] { line-height: 10px; }
}
path { fill: none; stroke-linecap: round; stroke-linejoin: round; }
line { fill: none; stroke-linecap: round; stroke-linejoin: round; }
#drawingArea rect { fill: none; }
#canvas ellipse { fill: none; }
#textToolInput { position: fixed; top: -1000px; left: 80px; width: 500px; },#textToolInput:focus { top: 5px; },text { font-family: Arial, Helvetica, sans-serif; user-select: none; }</style></svg>';

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->sampleSvgResult = '<div class="page_break"></div><img src="data:image/svg+xml;base64,' . (base64_encode(self::$sampleSvg)) . '" style="width: 600px"/>';
    }

    public function testWhiteboardSuccess(): void
    {
        $kernel = self::bootKernel();
        // Arrange


        $mockResponse = new MockResponse(
            self::$sampleSvg,
            [
                'http_code' => 200,
            ]
        );

        $httpClient = new MockHttpClient($mockResponse, 'http://whiteboardurl.com');

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(CreateSummaryService::class);
        $service->setHttpClient($httpClient);
        $whiteboardResponse = $service->createWhiteBoardSummary($room);


        self::assertSame('GET', $mockResponse->getRequestMethod());

        self::assertStringStartsWith('http://whiteboardurl.com/preview/' . $room->getUidReal(), $mockResponse->getRequestUrl());

        self::assertSame($whiteboardResponse, $this->sampleSvgResult);

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
    public function testWhiteboardNotFound(): void
    {
        $kernel = self::bootKernel();
        // Arrange
        $requestData = ['title' => 'Testing with Symfony HTTP Client'];
        $expectedRequestData = 'json_encode($requestData, JSON_THROW_ON_ERROR)';


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
        $whiteboardResponse = $service->createWhiteBoardSummary($room);


        self::assertSame('GET', $mockResponse->getRequestMethod());

        self::assertStringStartsWith('http://whiteboardurl.com/preview/' . $room->getUidReal(), $mockResponse->getRequestUrl());

        self::assertSame($whiteboardResponse, '');

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        // $myCustomService = static::getContainer()->get(CustomService::class);
    }
}
