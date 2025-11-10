<?php
declare(strict_types=1);

namespace App\Service\Result\Error;

enum ThemeUploadError
{
    case INVALID_THEME;
    case INVALID_ZIP;
    case NO_THEME_IN_ZIP;
}