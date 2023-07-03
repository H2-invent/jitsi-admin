<?php

namespace App\Exceptions;

use App\Entity\User;

class UserNotInAdressbookException extends \Exception
{
    private User $user;

    public function __construct(User $user)
    {
        parent::__construct('User not in Adressbook');
    }

    public function customMessage()
    {
        echo 'User: {$this->user->getUid()} is not in Adressbook';
    }
}
