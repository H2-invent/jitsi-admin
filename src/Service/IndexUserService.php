<?php

namespace App\Service;

use App\Entity\User;

class IndexUserService
{
    public function indexUser(?User $user): ?string
    {
        if ($user) {
            $index = '';
            $index .= strtolower($user->getUsername()) . ' ';
            $index .= strtolower($user->getEmail()) . ' ';
            $index .= strtolower($user->getFirstName()) . ' ';
            $index .= strtolower($user->getLastName());
            if (is_iterable($user->getSpezialProperties())) {
                foreach ($user->getSpezialProperties() as $key => $value) {
                    $index .= ' ';
                    $index .= strtolower($value);
                }
            }

            return $index;
        }
        return null;
    }
}
