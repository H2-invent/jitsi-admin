<?php

namespace App\Exceptions;

use App\Entity\User;

class UserNotInAdressbookException extends \Exception
{
    public function __construct()
    {
        parent::__construct('User not in Adressbook');
    }

    public function customMessage()
    {

    }
}
