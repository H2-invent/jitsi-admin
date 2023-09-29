<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Service\Summary\CreateSummaryService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CreateHeaderServiceTest extends KernelTestCase
{
    public static $headerHtml = "<table style=\"width: 500px\">
    <tbody>
    <tr>
  <td style=\"padding: 20px\" colspan=\"2\">
            <h1>TestMeeting: 0</h1>
        </td>
    </tr>
    <tr>
        <td style=\"width: 250px; padding: 16px; vertical-align:top\"><p>Testagenda:0</p></td>
        <td style=\"width: 250px; vertical-align:top\"><h3>Organisator</h3>Test1, 1234, User, Test</td>
    </tr>
    <tr>        
<td colspan=\"2\" style=\"vertical-align:top\">
            <p>
                <small>Alle Zeitangaben sind in der Zeitzone Europe/Berlin</small>   
         </p></td>
    </tr>
    <tr>
        <td>
            <table>
                <tbody>
                <tr>
                    <td style=\"padding: 16px; width: 125px; vertical-align: top\">
                        <h2>Geplant:</h2> <p>%s</p>
                        <p>%s - %s</p>
                    </td>
                    <td style=\"padding: 16px; width: 125px; vertical-align: top\">
                        <h2>Durchgeführt:</h2>
                        <table>
                            <tbody>
                                                        </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>

        </td>
        <td style=\"vertical-align: top\">
            <table width=\"450px\">
                <tbody>
                <tr>
                    <td style=\"padding: 16px; width: 125px; vertical-align: top\">
                        <h2>Teilnehmendenliste</h2>
                    </td>
                </tr>
                                    <tr>
                        <td style=\"padding: 8px\">Test1, 1234, User, Test</td>
                    </tr>
                                    <tr>
                        <td style=\"padding: 8px\">Test2, 1234, User2, Test2</td>
                    </tr>
                                    <tr>
                        <td style=\"padding: 8px\">test@local3.de</td>
                    </tr>
                                </tbody>
            </table>

        </td>
    </tr>
    </tbody>
</table>";

    public function testHeaderSuccess(): void
    {
        $kernel = self::bootKernel();
        // Arrange

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(CreateSummaryService::class);
        $headerResponse = $service->createHeader($room);


        self::assertEquals(trim(preg_replace('~[\r\n\s]+~', '', $headerResponse)), trim(preg_replace('~[\r\n\s]+~', '', sprintf(self::$headerHtml, $room->getStart()->format('d.m.Y'), $room->getStart()->format('H:i'), $room->getEnddate()->format('H:i')))));
    }
}
