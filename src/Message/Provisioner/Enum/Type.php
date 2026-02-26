<?php
declare(strict_types=1);

namespace App\Message\Provisioner\Enum;

enum Type: string
{
    case PROVISION = 'provision';
    case DELETION = 'deletion';
}
