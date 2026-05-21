<?php

namespace App\Service;

/**
 * Minimal, robust iCalendar (RFC 5545-ish) generator.
 * - Uses UTC everywhere (DTSTART/DTEND/DTSTAMP end with Z)
 * - No VTIMEZONE / TZID handling
 * - Clean REQUEST vs PUBLISH support
 * - Proper ATTENDEE / ORGANIZER formatting
 * - Line folding at 75 octets (good for Outlook/Gmail)
 */
class IcsService
{
    private const DT_UTC_FORMAT = 'Ymd\THis\Z';

    /** @var string REQUEST|PUBLISH|CANCEL */
    private string $method = 'REQUEST';

    /**
     * Each event is an associative array:
     * uid, summary, description, location, dtstart, dtend, sequence, organizerEmail, organizerName,
     * attendeeEmail, attendeeName, transp, class, status
     *
     * Dates can be DateTimeInterface OR strings (already in correct UTC format).
     */
    private array $events = [];

    public function setMethod(string $method): void
    {
        $method = strtoupper(trim($method));
        if (!in_array($method, ['REQUEST', 'PUBLISH', 'CANCEL'], true)) {
            $method = 'REQUEST';
        }
        $this->method = $method;
    }

    /**
     * Add one event.
     *
     * Expected keys (most optional):
     * - uid (string) recommended: "...@domain"
     * - summary (string)
     * - description (string)
     * - location (string)
     * - dtstart (DateTimeInterface|string)  (UTC preferred; if DateTimeInterface, will be converted to UTC)
     * - dtend (DateTimeInterface|string)
     * - sequence (int|string)
     * - organizerEmail (string) REQUIRED for REQUEST typically
     * - organizerName (string) optional
     * - attendeeEmail (string) optional (omit for PUBLISH / “self copy”)
     * - attendeeName (string) optional
     * - rsvp (bool) default true for REQUEST if attendee present
     * - status (string) e.g. CONFIRMED
     * - class (string) e.g. PUBLIC
     * - transp (string) OPAQUE|TRANSPARENT
     */
    public function addEvent(array $e): void
    {
        $normalized = [];

        // UID
        $uid = $e['uid'] ?? null;
        if (!$uid) {
            $uid = bin2hex(random_bytes(16)) . '@example.invalid';
        }
        $normalized['uid'] = (string)$uid;

        // Text fields
        foreach (['summary', 'description', 'location'] as $k) {
            if (isset($e[$k])) {
                $normalized[$k] = (string)$e[$k];
            }
        }

        // Times (UTC Z)
        if (isset($e['dtstart'])) {
            $normalized['dtstart'] = $this->toUtcZ($e['dtstart']);
        }
        if (isset($e['dtend'])) {
            $normalized['dtend'] = $this->toUtcZ($e['dtend']);
        }

        // Sequence
        if (isset($e['sequence'])) {
            $normalized['sequence'] = (string)(int)$e['sequence'];
        }

        if (isset($e['rdate'])) {
            $normalized['rdate'] = (string)$e['rdate'];
        }
        if (isset($e['recurrence-id'])) {
            $normalized['recurrence-id'] = (string)$e['recurrence-id'];
        }

        // Organizer
        if (isset($e['organizerEmail'])) {
            $normalized['organizerEmail'] = strtolower(trim((string)$e['organizerEmail']));
        }
        if (isset($e['organizerName'])) {
            $normalized['organizerName'] = $this->escapeParam((string)$e['organizerName']);
        }

        // Attendee
        if (!empty($e['attendeeEmail'])) {
            $normalized['attendeeEmail'] = strtolower(trim((string)$e['attendeeEmail']));
        }
        if (isset($e['attendeeName'])) {
            $normalized['attendeeName'] = $this->escapeParam((string)$e['attendeeName']);
        }
        $normalized['rsvp'] = array_key_exists('rsvp', $e) ? (bool)$e['rsvp'] : true;

        // Optional misc
        if (!empty($e['status'])) {
            $normalized['status'] = strtoupper(trim((string)$e['status']));
        }
        if (!empty($e['class'])) {
            $normalized['class'] = strtoupper(trim((string)$e['class']));
        }
        if (!empty($e['transp'])) {
            $t = strtoupper(trim((string)$e['transp']));
            $normalized['transp'] = in_array($t, ['OPAQUE', 'TRANSPARENT'], true) ? $t : 'OPAQUE';
        }

        if (!empty($e['url'])) {
            $normalized['url'] = (string)$e['url'];
        }
        $this->events[] = $normalized;
    }

    public function toString(): string
    {
        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//h2-invent//ics//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:' . $this->method;

        foreach ($this->events as $e) {
            $lines[] = 'BEGIN:VEVENT';

            // Required-ish
            $lines[] = 'UID:' . $e['uid'];
            $lines[] = 'DTSTAMP:' . $this->toUtcZ('now');

            if (!empty($e['dtstart'])) $lines[] = 'DTSTART:' . $e['dtstart'];
            if (!empty($e['dtend']))   $lines[] = 'DTEND:' . $e['dtend'];
            if (!empty($e['summary'])) $lines[] = 'SUMMARY:' .$this->escapeText($e['summary']);
            if (!empty($e['rdate'])) $lines[] = 'RDATE:' . $e['rdate'];
            if (!empty($e['recurrence-id'])) $lines[] = 'RECURRENCE-ID:' . $e['recurrence-id'];
            // Common optional
            if (!empty($e['location']))    $lines[] = 'LOCATION:' . $this->escapeText($e['location']);
            if (!empty($e['description'])) $lines[] = 'DESCRIPTION:' . $this->escapeText($e['description']);
            if (isset($e['sequence']))     $lines[] = 'SEQUENCE:' . $e['sequence'];
            if (!empty($e['status']))      $lines[] = 'STATUS:' . $e['status'];
            if (!empty($e['class']))       $lines[] = 'CLASS:' . $e['class'];
            $lines[] = 'TRANSP:' . ($e['transp'] ?? 'OPAQUE');

            // Organizer (recommended for REQUEST)
            if (!empty($e['organizerEmail'])) {
                if (!empty($e['organizerName'])) {
                    $lines[] = 'ORGANIZER;CN=' . $e['organizerName'] . ':MAILTO:' . $e['organizerEmail'];
                } else {
                    $lines[] = 'ORGANIZER:MAILTO:' . $e['organizerEmail'];
                }
            }

            // Attendee (only if present)
            if (!empty($e['attendeeEmail'])) {
                $params = [];
                if (!empty($e['attendeeName'])) $params[] = 'CN=' . $e['attendeeName'];
                $params[] = 'ROLE=REQ-PARTICIPANT';
                $params[] = 'PARTSTAT=NEEDS-ACTION';

                // For PUBLISH: usually omit attendee entirely. But if you keep it, RSVP should be FALSE.
                $rsvp = ($this->method === 'REQUEST') ? ($e['rsvp'] ? 'TRUE' : 'FALSE') : 'FALSE';
                $params[] = 'RSVP=' . $rsvp;

                $lines[] = 'ATTENDEE;' . implode(';', $params) . ':MAILTO:' . $e['attendeeEmail'];
            }

            if (!empty($e['url'])) {
                $escapedUrl = $this->escapeText($e['url']);

                $lines[] = 'URL:' . $escapedUrl;

                // Microsoft Outlook / 365
                $lines[] = 'X-MICROSOFT-CDO-ONLINE-MEETING:YES';
                $lines[] = 'X-MICROSOFT-CDO-ONLINE-MEETING-CONFLINK:' . $escapedUrl;
                $lines[] = 'X-MICROSOFT-CDO-ONLINE-MEETING-EXTERNALLINK:' . $escapedUrl;

                // Optional
                $lines[] = 'X-MICROSOFT-DISALLOW-COUNTER:FALSE';
                $lines[] = 'X-MS-OLK-CONFTYPE:0';
            }

            // Alarm (optional) – keep if you want
            $lines[] = 'BEGIN:VALARM';
            $lines[] = 'ACTION:DISPLAY';
            $lines[] = 'TRIGGER:-PT10M';
            $lines[] = 'DESCRIPTION:' . ($e['summary'] ?? 'Reminder');
            $lines[] = 'END:VALARM';

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        // Fold + CRLF
        $out = [];
        foreach ($lines as $line) {
            foreach ($this->foldLine($line) as $folded) {
                $out[] = $folded;
            }
        }
        return implode("\r\n", $out) . "\r\n";
    }

    /** Convert DateTimeInterface|string to UTC Z format. */
    public function toUtcZ(\DateTimeInterface|string $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $dt = (new \DateTimeImmutable($value->format('c')))->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format(self::DT_UTC_FORMAT);
        }

        $s = trim((string)$value);

        // If caller already provides Zulu format, keep it.
        if (preg_match('/^\d{8}T\d{6}Z$/', $s)) {
            return $s;
        }

        // If caller provides local-like "YYYYmmddTHHMMSS", assume it's in Europe/Berlin and convert to UTC.
        // (You can change default timezone here if you want.)
        if (preg_match('/^\d{8}T\d{6}$/', $s)) {
            $dt = \DateTimeImmutable::createFromFormat('Ymd\THis', $s, new \DateTimeZone('Europe/Berlin'));
            if ($dt instanceof \DateTimeImmutable) {
                return $dt->setTimezone(new \DateTimeZone('UTC'))->format(self::DT_UTC_FORMAT);
            }
        }

        // Fallback: let DateTime parse, then convert to UTC.
        $dt = new \DateTimeImmutable($s);
        return $dt->setTimezone(new \DateTimeZone('UTC'))->format(self::DT_UTC_FORMAT);
    }

    /** Escape TEXT (values after :) */
    private function escapeText(string $text): string
    {
        // Normalize newlines to \n and escape per RFC (basic)
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace("\\", "\\\\", $text);
        $text = str_replace(";", "\;", $text);
        $text = str_replace(",", "\,", $text);
        $text = str_replace("\n", "\\n", $text);
        return $text;
    }

    /** Escape PARAM values (e.g., CN=...) */
    private function escapeParam(string $s): string
    {
        $s = str_replace("\\", "\\\\", $s);
        $s = str_replace('"', '\"', $s);
        // If it contains special chars, quoting is safer. Many clients accept unquoted; keep simple:
        // You can enable quoting if you want: return '"' . $s . '"';
        $s = str_replace([";", ":"], ["\;", "\:"], $s);
        return $s;
    }

    /**
     * Fold a line at 75 octets (roughly). This implementation folds by bytes.
     * Each continuation line starts with a single space.
     */
    private function foldLine(string $line): array
    {
        $max = 75;
        if (strlen($line) <= $max) return [$line];

        $out = [];
        $rest = $line;

        while (strlen($rest) > $max) {
            $chunk = substr($rest, 0, $max);

            // Suche die letzte "sichere" Trennstelle im Chunk
            $breakPos = max(
                strrpos($chunk, ';') ?: 0,
                strrpos($chunk, ':') ?: 0,
                strrpos($chunk, ',') ?: 0
            );

            // Wenn keine sinnvolle Trennstelle gefunden wurde, hart schneiden
            if ($breakPos < 10) {
                $breakPos = $max;
            }

            $out[] = substr($rest, 0, $breakPos);
            $rest = ' ' . substr($rest, $breakPos);
        }

        $out[] = $rest;
        return $out;
    }
}