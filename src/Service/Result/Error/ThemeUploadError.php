<?php
declare(strict_types=1);

namespace App\Service\Result\Error;

enum ThemeUploadError: string
{
    case INVALID_THEME = 'Theme is invalid';
    case INVALID_ZIP = 'Could not open zip file';
    case NO_THEME_IN_ZIP = 'No Theme in the zip';
}
