<?php
declare(strict_types=1);

namespace App\Service\Result\Error;

enum ThemeUploadError: string
{
    case INVALID_THEME = 'Could not open zip file';
    case INVALID_ZIP = 'Theme is invalid';
    case NO_THEME_IN_ZIP = 'No Theme in the zip';
}