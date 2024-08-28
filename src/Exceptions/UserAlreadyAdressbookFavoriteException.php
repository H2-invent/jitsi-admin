<?php

namespace App\Exceptions;

use App\Entity\User;

class UserAlreadyAdressbookFavoriteException extends \Exception
{
    private User $user;

    public function __construct(User $user)
    {
        parent::__construct('User already in Adressbook favorite');
    }

    public function customMessage()
    {

    }
}
