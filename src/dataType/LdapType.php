<?php

namespace App\dataType;

use App\Service\ldap\LdapService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LdapType
{
    private $userDn;
    private $scope;
    private $mapper;
    private $url;
    private $userNameAttribute;
    private $serVerId;
    private $ldap;
    private $rdn;
    private $bindDn;
    private $password;
    private $bindType;
    private $objectClass;
    public static $ANONYMOUS = 1;
    public static $SIMPLE = 0;
    private $ldapService;
    private $specialFields;
    private $filter;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * @return mixed
     */
    public function getSpecialFields()
    {
        return $this->specialFields;
    }

    /**
     * @param mixed $specialFields
     */
    public function setSpecialFields($specialFields): void
    {
        $this->specialFields = $specialFields;
    }

    /**
     * @return mixed
     */
    public function getUserDn()
    {
        return $this->userDn;
    }

    /**
     * @param mixed $userDn
     */
    public function setUserDn($userDn): void
    {
        $this->userDn = $userDn;
    }

    /**
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param mixed $scope
     */
    public function setScope($scope): void
    {
        $this->scope = $scope;
    }

    /**
     * @return mixed
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @param mixed $mapper
     */
    public function setMapper($mapper): void
    {
        $this->mapper = $mapper;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getUserNameAttribute()
    {
        return $this->userNameAttribute;
    }

    /**
     * @param mixed $userNameAttribute
     */
    public function setUserNameAttribute($userNameAttribute): void
    {
        $this->userNameAttribute = $userNameAttribute;
    }

    /**
     * @return mixed
     */
    public function getSerVerId()
    {
        return $this->serVerId;
    }

    /**
     * @param mixed $serVerId
     */
    public function setSerVerId($serVerId): void
    {
        $this->serVerId = $serVerId;
    }

    /**
     * @return mixed
     */
    public function getLdap()
    {
        return $this->ldap;
    }

    /**
     * @param mixed $ldap
     */
    public function setLdap($ldap): void
    {
        $this->ldap = $ldap;
    }

    /**
     * @return mixed
     */
    public function getRdn()
    {
        return $this->rdn;
    }

    /**
     * @param mixed $rdn
     */
    public function setRdn($rdn): void
    {
        $this->rdn = $rdn;
    }

    /**
     * @return mixed
     */
    public function getBindDn()
    {
        return $this->bindDn;
    }

    /**
     * @param mixed $bindDn
     */
    public function setBindDn($bindDn): void
    {
        $this->bindDn = $bindDn;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getBindType()
    {
        return $this->bindType;
    }

    /**
     * @param mixed $bindType
     */
    public function setBindType($bindType): void
    {
        $this->bindType = $bindType;
    }

    /**
     * @return mixed
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * @param mixed $objectClass
     */
    public function setObjectClass($objectClass): void
    {
        $this->objectClass = $objectClass;
    }

    public function createLDAP()
    {
        try {
            $this->ldap = $this->ldapService->createLDAP(
                $this->url, $this->bindDn,
                $this->password,
                $this->bindType === 'simple' ? false : true
            );
            return true;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
     */
    public function setFilter($filter): void
    {
        $this->filter = $filter;
    }


    /**
     * @param $objectClassString
     * @return string
     * This Function build the Query String to find the user in the LDAP
     */
    public function buildObjectClass(): string
    {
        $objectclass = '(|';
        foreach (explode(',', $this->objectClass) as $data2) {
            $objectclass .= '(objectclass=' . $data2 . ')';
        }
        $objectclass .= ')';
        if($this->filter){
            $objectclass = ''.$objectclass.$this->filter;
        }
        $objectclass = '(&'.$objectclass.')';
        return $objectclass;
    }
}