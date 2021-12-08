<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ParticipantSearchService
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }
    public function generateUserwithoutEmptyUser($user){
        $res = array();
        foreach ($user as $data) {
            $res[] = array(
                'name' => $data->getFormatedName($this->parameterBag->get('laf_showName')),
                'id' => $data->getUsername()
            );
        }
        return $res;
    }
    public function generateUserwithEmptyUser($user,$searchString){
        $res = array();
        if (sizeof($user) === 0) {
            $res[] = array(
                'name' => $searchString,
                'id' => $searchString
            );
        }else{
            foreach ($user as $data) {
                $res[] = array(
                    'name' => $data->getFormatedName($this->parameterBag->get('laf_showName')),
                    'id' => $data->getUsername()
                );
            }
        }
        return $res;
    }
    public function generateGroup($group){
        $res = array();
        foreach ($group as $data) {
            $tmp = array('name' => '', 'user' => '');
            $tmpUser = array();
            $tmp['name'] = $data->getName();
            foreach ($data->getMember() as $m) {
                $tmpUser[] = $m->getUsername();
            }
            $tmp['user'] = implode("\n",$tmpUser );
            $res[] = $tmp;
        }
        return $res;
    }
}