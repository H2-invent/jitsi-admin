<?php
declare(strict_types=1);

namespace App\Message\ProvisionerStatus;

enum Status: string
{
    case FAILED = 'failed';
    case STARTED = 'started';
    case READY = 'ready';
}
