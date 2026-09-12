<?php

namespace App\Exceptions;

use App\Entity\User;

class UserAlreadyAdressbookFavoriteException extends \Exception
{
    public function __construct()
    {
        parent::__construct('User already in Adressbook favorite');
    }

    public function customMessage()
    {

    }
}
