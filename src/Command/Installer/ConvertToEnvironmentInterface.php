<?php

namespace App\Command\Installer;

interface ConvertToEnvironmentInterface
{
    public function getAsEnvironment(): array;

    public function getEnvironmentMap(): array;
}
