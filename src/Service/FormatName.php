<?php

namespace App\Service;

use App\Entity\User;

class FormatName
{
    public function formatName($string, User $user)
    {
        $pattern = '/[^\$]*user\.[a-zA-Z0-9.]*\$/';
        $patternItem = '/user\.[a-zA-Z0-9.]*\$/';
        $arr = null;
        preg_match_all($pattern, $string, $arr);
        $splitedName = $arr[0];

        foreach ($splitedName as $key => $data) {
            $fieldName = str_replace('$', '', $data);
            $fieldName = array_reverse(explode('.', $fieldName))[0];
            if ($key === array_key_first($splitedName)) {
                $data = preg_replace('/.+?(?=user\.)/', '', $data);
            }

            try {
                if (strpos($data, 'specialField') !== false) {
                    $spezialfield = $fieldName;
                    // we have a spezialField to read
                    if (isset($user->getSpezialProperties()[$spezialfield]) && $user->getSpezialProperties()[$spezialfield] !== '') {
                        $splitedName[$key] = preg_replace($patternItem, $user->getSpezialProperties()[$spezialfield], $data);
                    } else {
                        $splitedName[$key] = '';
                    }
                } else {
                    // we have a standard field to read

                    switch ($fieldName) {
                        case 'firstName':
                            $splitedName[$key] = $user->getFirstName() != '' ? preg_replace($patternItem, $user->getFirstName(), $data) : '';
                            break;
                        case 'lastName':
                            $splitedName[$key] = $user->getLastName() != '' ? preg_replace($patternItem, $user->getLastName(), $data) : '';
                            break;
                        case 'email':
                            $splitedName[$key] = $user->getEmail() != '' ? preg_replace($patternItem, $user->getEmail(), $data) : '';
                            break;
                        case 'username':
                            $splitedName[$key] = $user->getUsername() != '' ? preg_replace($patternItem, $user->getUsername(), $data) : '';
                            break;
                        default:
                            break;
                    }
                }
                if ($splitedName[$key] === '') {
                    unset($splitedName[$key]);
                }
            } catch (\Exception $exception) {
                $value = '';
            }
        }
        $string = '';
        foreach ($splitedName as $data) {
            $string .= $data;
        }
        if ($string ===''){
            $string = $user->getUsername();
        }
        return $string;
    }
}
