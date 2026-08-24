<?php
declare(strict_types=1);

namespace App\Service\Result\Error;

enum RecordingUploadError: string
{
    case UPLOAD_INCOMPLETE = 'Incomplete upload';
}
