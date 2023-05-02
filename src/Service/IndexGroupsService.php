<?php

namespace App\Service;

use App\Entity\AddressGroup;

class IndexGroupsService
{
    public function indexGroup(?AddressGroup $group): ?string
    {
        if ($group) {
            $index = '';
            $index .= strtolower($group->getName()) . ' ';
            return $index;
        }
        return null;
    }
}
