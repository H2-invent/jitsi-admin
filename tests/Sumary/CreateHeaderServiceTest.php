<?php

namespace App\Tests\Sumary;

use App\Repository\RoomsRepository;
use App\Service\Summary\CreateSummaryService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CreateHeaderServiceTest extends KernelTestCase
{
    public function testHeaderSuccess(): void
    {
        $kernel = self::bootKernel();

        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $service = self::getContainer()->get(CreateSummaryService::class);
        $headerResponse = $service->createHeader($room);

        $normalize = static fn (string $value): string => trim(preg_replace('~[\r\n\s]+~', '', $value));
        $normalized = $normalize($headerResponse);

        // Title and document structure.
        self::assertStringContainsString('<h1>TestMeeting: 0</h1>', $headerResponse);
        self::assertStringContainsString('<div class="doc-subtitle">Meeting</div>', $headerResponse);

        // Section headings are rendered with the translated labels.
        self::assertStringContainsString('>Agenda</div>', $headerResponse);
        self::assertStringContainsString('>Organisator</div>', $headerResponse);
        self::assertStringContainsString('>Geplant</div>', $normalize($headerResponse));
        self::assertStringContainsString('>Durchgeführt</div>', $headerResponse);
        self::assertStringContainsString('Teilnehmendenliste (' . count($room->getUser()) . ')</div>', $headerResponse);

        // Participants are rendered as inline chips.
        self::assertStringContainsString('<span class="chip">Test1, 1234, User, Test</span>', $headerResponse);

        // Agenda content.
        self::assertStringContainsString('Testagenda:0', $normalized);

        // Organiser and participants.
        self::assertStringContainsString('Test1, 1234, User, Test', $headerResponse);
        self::assertStringContainsString('Test2, 1234, User2, Test2', $headerResponse);
        self::assertStringContainsString('test@local3.de', $headerResponse);

        // Schedule information.
        self::assertStringContainsString($room->getStart()->format('d.m.Y'), $headerResponse);
        self::assertStringContainsString(
            $normalize($room->getStart()->format('H:i') . '&ndash;' . $room->getEnddate()->format('H:i')),
            $normalized
        );

        // Timezone note.
        self::assertStringContainsString('Europe/Berlin', $headerResponse);

        $this->assertSame('test', $kernel->getEnvironment());
    }
}
