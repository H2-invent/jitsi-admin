<?php

namespace App\Exceptions;

class UserNotInAdressbookException extends \Exception
{
    public function __construct()
    {
        parent::__construct('User not in Adressbook');
    }

    public function customMessage(): void
    {
    }
}
