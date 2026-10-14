<?php
declare(strict_types=1);

namespace App\Service\Provisioner;

enum ProvisionerStatus: string
{
    case IDLE = 'idle';

    case PROVISION_REQUESTED = 'provision_requested';
    case PROVISION_DONE = 'provision_done';
    case PROVISION_FAILED = 'provision_failed';

    case DELETION_REQUESTED = 'deletion_requested';
    case DELETION_DONE = 'deletion_done';
    case DELETION_FAILED = 'deletion_failed';
}
