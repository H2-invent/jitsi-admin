<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ParticipantSearchService
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag, private ThemeService $themeService, private LoggerInterface $logger)
    {
        $this->parameterBag = $parameterBag;
    }

    public function generateUserwithoutEmptyUser($user)
    {
        $res = [];
        foreach ($user as $data) {
            $tmp = [
                'name' => $this->buildShowInFrontendString($data),
                'nameNoIcon' => $this->buildShowInFrontendStringNoString($data),
                'id' => $data->getUsername(),
                'uid' => $data->getUid(),
                'roles' => ['participant', 'moderator']
            ];
            $this->filterForModerator($data, $tmp);

            $res[] = $tmp;
        }
        return $res;
    }

    public function generateUserwithEmptyUser($user, $searchString)
    {
        $res = [];
        if (sizeof($user) === 0) {
            $res[] = [
                'name' => $searchString,
                'id' => trim($searchString),
                'nameNoIcon' => $searchString,
                'roles' => ['participant', 'moderator']
            ];
        } else {
            foreach ($user as $data) {
                $tmp = [
                    'name' => $this->buildShowInFrontendString($data),
                    'nameNoIcon' => $this->buildShowInFrontendStringNoString($data),
                    'uid' => $data->getUid(),
                    'id' => trim($data->getUsername()),
                    'roles' => ['participant', 'moderator']
                ];
                $this->filterForModerator($data, $tmp);
                $res[] = $tmp;
            }
        }
        return $res;
    }

    public function generateGroup($group)
    {
        $res = [];
        foreach ($group as $data) {
            $tmp = ['name' => '', 'user' => ''];
            $tmpUser = [];
            $tmp['name'] = $data->getName();
            foreach ($data->getMember() as $m) {
                $tmpUser[] = trim($m->getUsername());
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
            if (isset($user->getSpezialProperties()[$key]) && $user->getSpezialProperties()[$key] !== '') {//Wenn das Spezialfeld im  User vorhanden ist, und wenn dieses im User nicht leer ist
                $res = '<i class="' . $data . '" title="' . $user->getSpezialProperties()[$key] . '" data-toggle="tooltip"></i> ' . $res;//dann nehme das Symbol aus dem Mapper und setzte es vor den Resultstring.
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

    public function filterForModerator(User $user, &$inputArr)
    {
        try {
            if ($user->getLdapUserProperties() && in_array($user->getLdapUserProperties()->getLdapNumber(), $this->themeService->getApplicationProperties('LDAP_DISALLOW_PROMOTE'))) {
                $inputArr['roles'] = $this->removeRoleFromArray($inputArr['roles'], 'moderator');
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
        return $inputArr;
    }

    public function removeRoleFromArray($inputArr, $role)
    {
        return \array_filter(
            $inputArr,
            static function ($element) use ($role) {
                return $element !== $role;
            }
        );
    }
}
