<?php
declare(strict_types=1);

namespace App\Message\Provisioner\Enum;

enum Status: string
{
    case FAILED = 'failed';
    case STARTED = 'started';
    case DONE = 'done';
}
