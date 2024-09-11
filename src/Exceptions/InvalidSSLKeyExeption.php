<?php

namespace App\Exceptions;

use App\Entity\User;

class InvalidSSLKeyExeption extends \Exception
{
    private User $user;

    public function __construct()
    {
        parent::__construct('Invalid SSL key fethced from Livekit Server');
    }

    public function customMessage()
    {

    }
}
