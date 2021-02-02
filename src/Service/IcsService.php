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
    protected $properties = array();
    private $isModerator;
public function __construct()
{
    $this->isModerator = false;
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

    private $available_properties = array(
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
    );
    private $appointments = array();
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
        $ics_props = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:' . $this->method,
        );

        // Build ICS properties - add header
        foreach ($this->appointments as $data) {
            $ics_props[] = 'BEGIN:VEVENT';

            $props = array();
            foreach ($data as $p => $q) {

                if ($this->isModerator){
                    $props[strtoupper($p . ($p === 'attendee' ? ';RSVP=false:MAILTO' : ''))] = $q;
                }else{
                    $props[strtoupper($p . ($p === 'attendee' ? ';RSVP=true:MAILTO' : ''))] = $q;
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
                $ics_props[] = "$k:$v";
            }

            $ics_props[] = 'END:VEVENT';
        }

        // Build ICS properties - add footer

        $ics_props[] = 'END:VCALENDAR';
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


}
