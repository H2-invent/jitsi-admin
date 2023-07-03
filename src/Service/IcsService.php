<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

class IcsService
{
    const DT_FORMAT = 'Ymd\THis\Z';
    protected $properties = [];
    private $isModerator;
    private $timezoneId;
    private $timezoneStart;
    private $timezoneEnd;
    private $timeZone;

    public function __construct()
    {
        $this->isModerator = false;
        $this->timezoneId = date_default_timezone_get();
        $this->timezoneStart = new \DateTime();
    }

    /**
     * @return string
     */
    public function getTimezoneId(): string
    {
        return $this->timezoneId;
    }

    /**
     * @param string $timezoneId
     */
    public function setTimezoneId(string $timezoneId): void
    {
        $this->timezoneId = $timezoneId;
    }

    /**
     * @return \DateTime
     */
    public function getTimezoneStart(): \DateTime
    {
        return $this->timezoneStart;
    }

    /**
     * @param \DateTime $timezoneStart
     */
    public function setTimezoneStart(\DateTime $timezoneStart): void
    {
        $this->timezoneStart = $timezoneStart;
    }

    /**
     * @return mixed
     */
    public function getTimezoneEnd()
    {
        return $this->timezoneEnd;
    }

    /**
     * @param mixed $timezoneEnd
     */
    public function setTimezoneEnd($timezoneEnd): void
    {
        $this->timezoneEnd = $timezoneEnd;
    }

    /**
     * @return mixed
     */
    public function getIsModerator()
    {
        return $this->isModerator;
    }

    /**
     * @param mixed $isModerator
     */
    public function setIsModerator($isModerator): void
    {
        $this->isModerator = $isModerator;
    }

    private $available_properties = [
        'description',
        'dtend',
        'dtstart',
        'location',
        'summary',
        'rrule',
        'uid',
        'sequense',
        'organizer',
        'attendee'
    ];
    private $appointments = [];
    private $method; // REQUEST,CANCELED,PUBLISH

    public function set($key, $val = false)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            if (in_array($key, $this->available_properties)) {
                $this->properties[$key] = $this->sanitizeVal($val, $key);
            }
        }
        $this->appointments[] = $this->properties;
    }

    public function add($key)
    {
        $this->appointments[] = $key;
    }

    public function setMethod($method)
    {

        $this->method = $method;
    }

    public function toString()
    {
        $rows = $this->buildProps();
        $res = implode("\r\n", $rows);
        return $res;
    }

    private function buildProps()
    {
        // Build ICS properties - add header
        $ics_props = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:' . $this->method,
        ];
        $ics_props = array_merge($ics_props, $this->generateTimeZoneString($this->timezoneId, (clone $this->timezoneStart)->modify('first day of January last year'), (clone $this->timezoneStart)->modify('last day of december this year')));

        // Build ICS properties - add header
        foreach ($this->appointments as $data) {
            $ics_props[] = 'BEGIN:VEVENT';

            $props = [];
            foreach ($data as $p => $q) {
                if ($this->isModerator) {
                    $props[strtoupper($p . ($p === 'attendee' ? ';RSVP=false:MAILTO' : ''))] = $q;
                } else {
                    $props[strtoupper($p . ($p === 'attendee' ? ';ROLE=REQ-PARTICIPANT; PARTSTAT=NEEDS-ACTION;RSVP=true:MAILTO' : ''))] = $q;
                }
            }
            // Set some default values
            $props['DTSTAMP'] = $this->formatTimestamp('now');
            $props['LAST-MODIFIED'] = $this->formatTimestamp('now');
            if (!$props['UID']) {
                $props['UID'] = uniqid('sd', true);
            }

            // Append properties
            foreach ($props as $k => $v) {
                if ($k === 'DTSTART' || $k === 'DTEND') {
                    $k = $k . ';TZID=' . $this->timezoneId . '';
                }
                $ics_props[] = "$k:$v";
            }
            $ics_props[] = 'BEGIN:VALARM';
            $ics_props[] = 'ACTION:DISPLAY';
            $ics_props[] = 'TRIGGER:-PT10M';
            $ics_props[] = 'DESCRIPTION:' . $data['summary'];
            $ics_props[] = 'END:VALARM';
            $ics_props[] = 'END:VEVENT';
        }
        $ics_props[] = 'END:VCALENDAR';

        // Build ICS properties - add footer

        return $ics_props;
    }

    private function sanitizeVal($val, $key = false)
    {
        switch ($key) {
            case 'dtend':
                break;
            case 'dtstamp':
                $val = $this->formatTimestamp($val);
                break;
            case 'dtstart':
                $val = $this->formatTimestamp($val);
                break;
            default:
                $val = $this->escape_string($val);
                break;
        }
        return $val;
    }

    private function formatTimestamp($timestamp)
    {
        $dt = new \DateTime($timestamp);
        return $dt->format(self::DT_FORMAT);
    }

    private function escape_string($str)
    {
        return preg_replace('/([\,;])/', '\\\$1', $str);
    }

    private function generateTimeZoneString(string $timeZone, \DateTime $start, \DateTime $end)
    {

        $tmpTimeZone = new \DateTimeZone($timeZone);
        $transitions = $tmpTimeZone->getTransitions($start->getTimestamp(), $end->getTimestamp());
        $transitions = array_splice($transitions, 1);
        $ics_props[] = 'BEGIN:VTIMEZONE';
        $ics_props[] = 'TZID:' . $timeZone;
        if (sizeof($transitions) == 0) {
            $transitions[]['time'] = (clone $start)->format('Y-m-d H:i:s');
        }
        foreach ($transitions as $data) {
            $tmpDate = new \DateTime($data['time']);
            $tmpDate->setTimezone($tmpTimeZone);

            $daylight = $tmpDate->format('I') == 1 ? true : false;
            if ($daylight) {
                $ics_props[] = 'BEGIN:DAYLIGHT';
            } else {
                $ics_props[] = 'BEGIN:STANDARD';
            }

            $ics_props[] = 'DTSTART:' . $tmpDate->format('Ymd') . 'T' . $tmpDate->format('His');//19501029T020000';
            $ics_props[] = 'TZOFFSETTO:' . $tmpDate->format('O');//+0100';
            $ics_props[] = 'TZOFFSETFROM:' . $tmpDate->modify('-1day')->format('O');//+0200';

            if ($daylight) {
                $ics_props[] = 'END:DAYLIGHT';
            } else {
                $ics_props[] = 'END:STANDARD';
            }
        }
        $ics_props[] = 'END:VTIMEZONE';
        return $ics_props;
    }
}
