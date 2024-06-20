<?php

namespace App\dataType;

use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LdapType
{
    private $userDn;
    private $scope;
    private $mapper;
    private $url;
    private $userNameAttribute;
    private $serVerId;
    private Ldap $ldap;
    private $rdn;
    private $bindDn;
    private $password;
    private $bindType;
    private $objectClass;
    public static $ANONYMOUS = 1;
    public static $SIMPLE = 0;
    private $specialFields;
    private $filter;
    private $dryRun = false;
    private $LDAP_DEPUTY_GROUP_OBJECTCLASS;
    private $LDAP_DEPUTY_GROUP_DN;
    private $LDAP_DEPUTY_GROUP_LEADER;
    private $LDAP_DEPUTY_GROUP_MEMBERS;
    private $LDAP_DEPUTY_GROUP_FILTER;
    private $isHealthy = false;
    private bool $IS_SIP_VIDEO = false;


    public function __toString(): string
    {
        return $this->serVerId;
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

        $anonym = $this->bindType === 'simple' ? false : true;
        $validator = Validation::createValidator();
        try {
            $tmp = Ldap::create('ext_ldap', ['connection_string' => $this->url]);
            $isUrl = $this->isValidLdapUrl($this->url);
            if ($isUrl) {
                if (!$anonym) {
                    $tmp->bind($this->bindDn, $this->password);
                } else {
                    $tmp->bind();
                }
            } else {
                throw new \Exception('invalid Bind URL');
            }
            $this->ldap = $tmp;
            $this->isHealthy = true;
            return $tmp;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    public function isValidLdapUrl(string $url): bool
    {
        $regex = '/^ldaps?:\/\/((\d{1,3}\.){3}\d{1,3}|[a-zA-Z0-9-]{1,63}(\.[a-zA-Z0-9-]{1,63})*\.[a-zA-Z]{2,6})(:\d+)?$/m';
        ;

        $isUrl = preg_match($regex, $url);
        return $isUrl > 0;
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
        if ($this->filter) {
            $objectclass .= $this->filter;
        }
        $objectclass = '(&' . $objectclass . ')';
        return $objectclass;
    }

    /**
     * @param $objectClassString
     * @return string
     * This Function build the Query String to find the user in the LDAP
     */
    public function buildObjectClassDeputy(): string
    {
        $objectclass = '(|';
        foreach (explode(',', $this->LDAP_DEPUTY_GROUP_OBJECTCLASS) as $data2) {
            $objectclass .= '(objectclass=' . $data2 . ')';
        }
        $objectclass .= ')';

        if ($this->LDAP_DEPUTY_GROUP_FILTER) {
            $objectclass .= $this->LDAP_DEPUTY_GROUP_FILTER;
            $objectclass = '(&' . $objectclass . ')';
        }

        return $objectclass;
    }

    /**
     * this function queries for users in the ldap
     * @param Ldap $ldap
     * @param string $userDn
     * @param string $objectclass
     * @param string $scope
     * @return \Symfony\Component\Ldap\Entry[]
     */
    public function retrieveUser()
    {

        $options = [
            'scope' => $this->scope,
        ];

        $query = $this->ldap->query($this->userDn, $this->buildObjectClass(), $options);
        $user = $query->execute();
        return $user->toArray();
    }


    public function retrieveDeputies()
    {

        $options = [
            'scope' => $this->scope,
        ];

        $query = $this->ldap->query($this->LDAP_DEPUTY_GROUP_DN, $this->buildObjectClassDeputy(), $options);
        $user = $query->execute();
        return $user->toArray();
    }

    /**
     * @return mixed
     */
    public function getDryRun()
    {
        return $this->dryRun;
    }

    /**
     * @param mixed $dryRun
     */
    public function setDryRun($dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    /**
     * @return mixed
     */
    public function getLDAPDEPUTYGROUPOBJECTCLASS()
    {
        return $this->LDAP_DEPUTY_GROUP_OBJECTCLASS;
    }

    /**
     * @param mixed $LDAP_DEPUTY_GROUP_OBJECTCLASS
     */
    public function setLDAPDEPUTYGROUPOBJECTCLASS($LDAP_DEPUTY_GROUP_OBJECTCLASS): void
    {
        $this->LDAP_DEPUTY_GROUP_OBJECTCLASS = $LDAP_DEPUTY_GROUP_OBJECTCLASS;
    }

    /**
     * @return mixed
     */
    public function getLDAPDEPUTYGROUPDN()
    {
        return $this->LDAP_DEPUTY_GROUP_DN;
    }

    /**
     * @param mixed $LDAP_DEPUTY_GROUP_DN
     */
    public function setLDAPDEPUTYGROUPDN($LDAP_DEPUTY_GROUP_DN): void
    {
        $this->LDAP_DEPUTY_GROUP_DN = $LDAP_DEPUTY_GROUP_DN;
    }

    /**
     * @return mixed
     */
    public function getLDAPDEPUTYGROUPLEADER()
    {
        return $this->LDAP_DEPUTY_GROUP_LEADER;
    }

    /**
     * @param mixed $LDAP_DEPUTY_GROUP_LEADER
     */
    public function setLDAPDEPUTYGROUPLEADER($LDAP_DEPUTY_GROUP_LEADER): void
    {
        $this->LDAP_DEPUTY_GROUP_LEADER = $LDAP_DEPUTY_GROUP_LEADER;
    }

    /**
     * @return mixed
     */
    public function getLDAPDEPUTYGROUPMEMBERS()
    {
        return $this->LDAP_DEPUTY_GROUP_MEMBERS;
    }

    /**
     * @param mixed $LDAP_DEPUTY_GROUP_MEMBERS
     */
    public function setLDAPDEPUTYGROUPMEMBERS($LDAP_DEPUTY_GROUP_MEMBERS): void
    {
        $this->LDAP_DEPUTY_GROUP_MEMBERS = $LDAP_DEPUTY_GROUP_MEMBERS;
    }

    /**
     * @return mixed
     */
    public function getLDAPDEPUTYGROUPFILTER()
    {
        return $this->LDAP_DEPUTY_GROUP_FILTER;
    }

    /**
     * @param mixed $LDAP_DEPUTY_GROUP_FILTER
     */
    public function setLDAPDEPUTYGROUPFILTER($LDAP_DEPUTY_GROUP_FILTER): void
    {
        $this->LDAP_DEPUTY_GROUP_FILTER = $LDAP_DEPUTY_GROUP_FILTER;
    }

    /**
     * @return bool
     */
    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

    /**
     * @param bool $isHealthy
     */
    public function setIsHealthy(bool $isHealthy): void
    {
        $this->isHealthy = $isHealthy;
    }

    public function getISSIPVIDEO(): bool
    {
        return $this->IS_SIP_VIDEO;
    }

    public function setISSIPVIDEO(bool $IS_SIP_VIDEO): void
    {
        $this->IS_SIP_VIDEO = $IS_SIP_VIDEO;
    }



}
