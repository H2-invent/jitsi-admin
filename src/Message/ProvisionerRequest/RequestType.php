<?php
declare(strict_types=1);

namespace App\Message\ProvisionerRequest;

enum RequestType: string
{
    case PROVISION = 'provision';
    case DELETION = 'deletion';
}
