<?php
declare(strict_types=1);

namespace App\Message\Provisioner\Enum;

enum ServerFeature: string
{
    case RTC = 'rtc';
    case SIP = 'sip';
    case RECORDING = 'recording';
}
