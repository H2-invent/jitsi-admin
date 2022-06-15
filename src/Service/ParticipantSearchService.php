<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ParticipantSearchService
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function generateUserwithoutEmptyUser($user)
    {
        $res = array();
        foreach ($user as $data) {
            $res[] = array(
                'name' => $this->buildShowInFrontendString($data),
                'id' => $data->getUsername()
            );
        }
        return $res;
    }

    public function generateUserwithEmptyUser($user, $searchString)
    {
        $res = array();
        if (sizeof($user) === 0) {
            $res[] = array(
                'name' => $searchString,
                'id' => $searchString
            );
        } else {
            foreach ($user as $data) {
                $res[] = array(
                    'name' => $this->buildShowInFrontendString($data),
                    'id' => $data->getUsername()
                );
            }
        }
        return $res;
    }

    public function generateGroup($group)
    {
        $res = array();
        foreach ($group as $data) {
            $tmp = array('name' => '', 'user' => '');
            $tmpUser = array();
            $tmp['name'] = $data->getName();
            foreach ($data->getMember() as $m) {
                $tmpUser[] = $m->getUsername();
            }
            $tmp['user'] = implode("\n", $tmpUser);
            $res[] = $tmp;
        }
        return $res;
    }

    public function buildShowInFrontendString(User $user)
    {
        $res = '';
        $res .= $user->getFormatedName($this->parameterBag->get('laf_showName'));
        $mapper = json_decode($this->parameterBag->get('laf_icon_mapping_search'), true);

        foreach ($mapper as $key => $data) {//Iterie über alle Icon Mapper Symbole
            if (isset($user->getSpezialProperties()[$key]) && $user->getSpezialProperties()[$key]!== ''){//Wenn das Spezialfeld im  User vorhanden ist, und wenn dieses im User nicht leer ist
                $res = '<i class="'.$data.'" title="'.$user->getSpezialProperties()[$key].'" data-toggle="tooltip"></i> '.$res;//dann nehme das Symbol aus dem Mapper und setzte es vor den Resultstring.
            }
        }
        return $res;

    }
    public function buildShowInFrontendStringNoString(User $user)
    {
        $res = '';
        $res .= $user->getFormatedName($this->parameterBag->get('laf_showName'));
        return $res;

    }
}