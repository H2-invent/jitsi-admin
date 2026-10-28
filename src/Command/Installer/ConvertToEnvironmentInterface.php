<?php

namespace App\Command\Installer;

interface ConvertToEnvironmentInterface
{
    /**
     * @return array<int, string>
     */
    public function getAsEnvironment(): array;

    /**
     * @return array<string, string>
     */
    public function getEnvironmentMap(): array;
}
