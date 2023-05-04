<?php

namespace App\Command\Installer;

use RuntimeException;

trait ConvertToEnvironmentTrait
{
    public function getAsEnvironment(): array
    {
        $result = [];

        if ($this instanceof ConvertToEnvironmentInterface) {
            $result = [];

            foreach ($this->getEnvironmentMap() as $key => $attribute) {
                $result[] = $key . '="' . $this->{$attribute}() . '"' . PHP_EOL;
            }

            return $result;
        }

        throw new RuntimeException(
            'Class must implement ' . ConvertToEnvironmentInterface::class . 'Interface and all methods named by getEnvironmentMap'
        );
    }
}
