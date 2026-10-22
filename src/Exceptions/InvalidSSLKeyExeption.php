<?php

namespace App\Exceptions;

class InvalidSSLKeyExeption extends \Exception
{
    public function __construct()
    {
        parent::__construct('Invalid SSL key fethced from Livekit Server');
    }

    public function customMessage(): void
    {

    }
}
