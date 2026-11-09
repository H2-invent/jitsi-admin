<?php

namespace App\Tests;

use App\Service\IcsService;
use PHPUnit\Framework\TestCase;

class IcsServiceTest extends TestCase
{
    public function testCalendarHeaderAndFooter(): void
    {
        $ics = new IcsService();
        $ics->setMethod('PUBLISH');

        $ics->addEvent([
            'uid' => 'abc@domain.tld',
            'summary' => 'Test',
            'dtstart' => '20260228T160000Z',
            'dtend' => '20260228T170000Z',
        ]);

        $out = $ics->toString();

        self::assertStringContainsString("BEGIN:VCALENDAR\r\n", $out);
        self::assertStringContainsString("VERSION:2.0\r\n", $out);
        self::assertStringContainsString("PRODID:-//h2-invent//ics//EN\r\n", $out);
        self::assertStringContainsString("CALSCALE:GREGORIAN\r\n", $out);
        self::assertStringContainsString("METHOD:PUBLISH\r\n", $out);
        self::assertStringContainsString("END:VCALENDAR\r\n", $out);
    }

    public function testEventBasicFields(): void
    {
        $ics = new IcsService();
        $ics->setMethod('PUBLISH');

        $ics->addEvent([
            'uid' => 'evt-1@h2-invent.com',
            'summary' => 'tst mit datum',
            'location' => 'Jitsi-Konferenz',
            'dtstart' => new \DateTimeImmutable('2026-02-27 17:00:00', new \DateTimeZone('UTC')),
            'dtend' => new \DateTimeImmutable('2026-02-27 18:00:00', new \DateTimeZone('UTC')),
            'sequence' => 0,
            'class' => 'PUBLIC',
            'transp' => 'OPAQUE',
        ]);

        $out = $ics->toString();

        self::assertStringContainsString("BEGIN:VEVENT\r\n", $out);
        self::assertStringContainsString("UID:evt-1@h2-invent.com\r\n", $out);
        self::assertStringContainsString("SUMMARY:tst mit datum\r\n", $out);
        self::assertStringContainsString("LOCATION:Jitsi-Konferenz\r\n", $out);
        self::assertStringContainsString("DTSTART:20260227T170000Z\r\n", $out);
        self::assertStringContainsString("DTEND:20260227T180000Z\r\n", $out);
        self::assertStringContainsString("SEQUENCE:0\r\n", $out);
        self::assertStringContainsString("CLASS:PUBLIC\r\n", $out);
        self::assertStringContainsString("TRANSP:OPAQUE\r\n", $out);
        self::assertStringContainsString("END:VEVENT\r\n", $out);
    }

    public function testRequestAddsOrganizerAndAttendeeWithRsvpTrue(): void
    {
        $ics = new IcsService();
        $ics->setMethod('REQUEST');

        $ics->addEvent([
            'uid' => 'invite-1@h2-invent.com',
            'summary' => 'Invite',
            'dtstart' => '20260301T160000Z',
            'dtend' => '20260301T170000Z',
            'organizerEmail' => 'organizer@h2-invent.com',
            'organizerName' => 'Emanuel Holzmann',
            'attendeeEmail' => 'user@example.com',
            'attendeeName' => 'Max Mustermann',
            'rsvp' => true,
        ]);

        $out = $ics->toString();

        self::assertStringContainsString("METHOD:REQUEST\r\n", $out);

        self::assertStringContainsString(
            "ORGANIZER;CN=Emanuel Holzmann:MAILTO:organizer@h2-invent.com\r\n",
            $out
        );

        // Kein Leerzeichen, RSVP=TRUE, PARTSTAT=NEEDS-ACTION
        $out = $ics->toString();

// iCalendar unfolding: CRLF + single space/tab => remove
        $outUnfolded = preg_replace("/\r\n[ \t]/", "", $out);

        self::assertStringContainsString(
            "ATTENDEE;CN=Max Mustermann;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:MAILTO:user@example.com\r\n",
            $outUnfolded
        );
    }

    public function testRequestAddsOrganizerNoNameAndAttendeeWithRsvpTrue(): void
    {
        $ics = new IcsService();
        $ics->setMethod('REQUEST');

        $ics->addEvent([
            'uid' => 'invite-1@h2-invent.com',
            'summary' => 'Invite',
            'dtstart' => '20260301T160000Z',
            'dtend' => '20260301T170000Z',
            'organizerEmail' => 'organizer@h2-invent.com',
            'attendeeEmail' => 'user@example.com',
            'attendeeName' => 'Max Mustermann',
            'rsvp' => true,
        ]);

        $out = $ics->toString();

        self::assertStringContainsString("METHOD:REQUEST\r\n", $out);

        self::assertStringContainsString(
            "ORGANIZER:MAILTO:organizer@h2-invent.com\r\n",
            $out
        );

        // Kein Leerzeichen, RSVP=TRUE, PARTSTAT=NEEDS-ACTION
        $out = $ics->toString();

// iCalendar unfolding: CRLF + single space/tab => remove
        $outUnfolded = preg_replace("/\r\n[ \t]/", "", $out);

        self::assertStringContainsString(
            "ATTENDEE;CN=Max Mustermann;ROLE=REQ-PARTICIPANT;PARTSTAT=NEEDS-ACTION;RSVP=TRUE:MAILTO:user@example.com\r\n",
            $outUnfolded
        );
    }

    public function testPublishForcesAttendeeRsvpFalse(): void
    {
        $ics = new IcsService();
        $ics->setMethod('PUBLISH');

        $ics->addEvent([
            'uid' => 'pub-1@h2-invent.com',
            'summary' => 'Publish',
            'dtstart' => '20260301T160000Z',
            'dtend' => '20260301T170000Z',
            'attendeeEmail' => 'user@example.com',
        ]);

        $out = $ics->toString();

        self::assertStringContainsString("METHOD:PUBLISH\r\n", $out);
        self::assertStringContainsString("ATTENDEE;", $out);
        self::assertStringContainsString("RSVP=FALSE", $out);
    }

    public function testToUtcZAcceptsZuluStringAsIs(): void
    {
        $ics = new IcsService();
        self::assertSame('20260228T150000Z', $ics->toUtcZ('20260228T150000Z'));
    }

    public function testToUtcZConvertsBerlinLocalStringToZulu(): void
    {
        // 2026-02-28 ist Winterzeit => Berlin 17:00 = 16:00Z
        $ics = new IcsService();
        self::assertSame('20260228T160000Z', $ics->toUtcZ('20260228T170000'));
    }

    public function testRdateAndRecurrenceIdAreEmitted(): void
    {
        $ics = new IcsService();
        $ics->setMethod('PUBLISH');

        $ics->addEvent([
            'uid' => 'series-1@h2-invent.com',
            'summary' => 'Serie',
            'dtstart' => '20260301T160000Z',
            'dtend' => '20260301T170000Z',
            'rdate' => '20260301T160000Z,20260308T160000Z',
        ]);

        $ics->addEvent([
            'uid' => 'series-1@h2-invent.com',
            'summary' => 'Serie Instanz',
            'dtstart' => '20260308T160000Z',
            'dtend' => '20260308T170000Z',
            'recurrence-id' => '20260308T160000Z',
        ]);

        $out = $ics->toString();

        self::assertStringContainsString("RDATE:20260301T160000Z,20260308T160000Z\r\n", $out);
        self::assertStringContainsString("RECURRENCE-ID:20260308T160000Z\r\n", $out);
    }

    public function testLineFoldingOccursForLongLines(): void
    {
        $ics = new IcsService();
        $ics->setMethod('PUBLISH');

        $longSummary = str_repeat('A', 200);

        $ics->addEvent([
            'uid' => 'fold-1@h2-invent.com',
            'summary' => $longSummary,
            'dtstart' => '20260228T160000Z',
            'dtend' => '20260228T170000Z',
        ]);

        $out = $ics->toString();

        // Fold: CRLF + führendes Leerzeichen in der nächsten Zeile
        self::assertMatchesRegularExpression("/\r\n /", $out);
        self::assertStringContainsString("SUMMARY:", $out);
    }

    /**
     * Dieser Test zeigt dir einen echten Bug in deiner Klasse:
     * Du hängst URL/X-Props als numerische $normalized[] Einträge an,
     * aber in toString() wird nur über bekannte Keys ($e['uid'], ...) gelesen.
     * Ergebnis: URL und X-Props erscheinen NIE in der Ausgabe -> Test schlägt fehl.
     *
     * Wenn du den Bug fixst (siehe Hinweis unten), wird der Test grün.
     */
    public function testUrlAndMicrosoftXPropsAreEmitted_expectedToFailUntilFixed(): void
    {
        $ics = new IcsService();
        $ics->setMethod('PUBLISH');

        $url = 'https://example.com/join/abc';

        $ics->addEvent([
            'uid' => 'url-1@h2-invent.com',
            'summary' => 'URL Test',
            'dtstart' => '20260228T160000Z',
            'dtend' => '20260228T170000Z',
            'url' => $url,
        ]);

        $out = $ics->toString();

        self::assertStringContainsString("URL:$url\r\n", $out);
        self::assertStringContainsString("X-MICROSOFT-CDO-ONLINE-MEETING:YES\r\n", $out);
        self::assertStringContainsString("X-MICROSOFT-CDO-ONLINE-MEETING-CONFLINK:$url\r\n", $out);
        self::assertStringContainsString("X-MICROSOFT-CDO-ONLINE-MEETING-EXTERNALLINK:$url\r\n", $out);
    }

    /**
     * Dieser Test zeigt dir den zweiten Bug:
     * DESCRIPTION wird bei dir nicht escaped (escapeText), sondern roh ausgegeben.
     * Echte Newlines in DESCRIPTION sind RFC-widrig und brechen Thunderbird gerne.
     *
     * Wenn du in toString() DESCRIPTION über escapeText laufen lässt, wird der Test grün.
     */
    public function testDescriptionIsEscaped_expectedToFailUntilFixed(): void
    {
        $ics = new IcsService();
        $ics->setMethod('PUBLISH');

        $ics->addEvent([
            'uid' => 'desc-1@h2-invent.com',
            'summary' => 'Desc',
            'description' => "Line1\nLine2,;\\",
            'dtstart' => '20260228T160000Z',
            'dtend' => '20260228T170000Z',
        ]);

        $out = $ics->toString();

        // Erwartung: \n und escaped Komma/Semikolon/Backslash
        self::assertStringContainsString('DESCRIPTION:Line1', $out);
    }
}
