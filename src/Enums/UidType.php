<?php

namespace App\Enums;

enum UidType: string
{
    case CALENDLY    = 'cal';
    case CALLOUT     = 'clo';
    case MESSAGE     = 'msg';
    case MODERATOR   = 'mod';
    case OWN_ROOM    = 'owr';
    case PARTICIPANT = 'par';
    case RECORDING   = 'rec';
    case REGISTER    = 'reg';
    case REPEATER    = 'rep';
    case ROOM        = 'rom';
    case SCHEDULE    = 'scd';
    case SECRET      = 'sec';
    case SESSION     = 'ses';
    case SUBSCRIBER  = 'sub';
    case TEMP        = 'tmp';
    case THEME       = 'thm';
    case USER        = 'usr';
}
