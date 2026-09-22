<?php
declare(strict_types=1);

namespace App\Service\Result\Error;

enum RecordingFinalizeError: string
{
    case NO_CHUNKS_FOUND = 'Could not find chunks on disk';
    case NO_RECORDING_FOUND = 'Could not find recording via uid';
    case COULD_NOT_WRITE_FINAL_FILE = 'Could not write the final file';
}
