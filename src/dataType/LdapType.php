<?php

namespace App\dataType;

use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LdapType
{
    /** @var string */
    private $userDn;
    /** @var string */
    private $scope;
    /** @var array<string, mixed> */
    private $mapper;
    /** @var string */
    private $url;
    /** @var string */
    private $userNameAttribute;
    /** @var string */
    private $serVerId;
    private Ldap $ldap;
    /** @var string */
    private $rdn;
    /** @var string */
    private $bindDn;
    /** @var string */
    private $password;
    /** @var string */
    private $bindType;
    /** @var string */
    private $objectClass;
    public static int $ANONYMOUS = 1;
    public static int $SIMPLE = 0;
    /** @var array<string, mixed> */
    private $specialFields;
    /** @var string|null */
    private $filter;
    private bool $dryRun = false;
    /** @var string */
    private $LDAP_DEPUTY_GROUP_OBJECTCLASS;
    /** @var string */
    private $LDAP_DEPUTY_GROUP_DN;
    /** @var string */
    private $LDAP_DEPUTY_GROUP_LEADER;
    /** @var string */
    private $LDAP_DEPUTY_GROUP_MEMBERS;
    /** @var string|null */
    private $LDAP_DEPUTY_GROUP_FILTER;
    private bool $isHealthy = false;
    private bool $IS_SIP_VIDEO = false;


    public function __toString(): string
    {
        return $this->serVerId;
    }

    public function getSpecialFields(): mixed
    {
        return $this->specialFields;
    }

    public function setSpecialFields(mixed $specialFields): void
    {
        $this->specialFields = $specialFields;
    }

    public function getUserDn(): mixed
    {
        return $this->userDn;
    }

    public function setUserDn(mixed $userDn): void
    {
        $this->userDn = $userDn;
    }

    public function getScope(): mixed
    {
        return $this->scope;
    }

    public function setScope(mixed $scope): void
    {
        $this->scope = $scope;
    }

    public function getMapper(): mixed
    {
        return $this->mapper;
    }

    public function setMapper(mixed $mapper): void
    {
        $this->mapper = $mapper;
    }

    public function getUrl(): mixed
    {
        return $this->url;
    }

    public function setUrl(mixed $url): void
    {
        $this->url = $url;
    }

    public function getUserNameAttribute(): mixed
    {
        return $this->userNameAttribute;
    }

    public function setUserNameAttribute(mixed $userNameAttribute): void
    {
        $this->userNameAttribute = $userNameAttribute;
    }

    public function getSerVerId(): mixed
    {
        return $this->serVerId;
    }

    public function setSerVerId(mixed $serVerId): void
    {
        $this->serVerId = $serVerId;
    }

    public function getLdap(): mixed
    {
        return $this->ldap;
    }

    public function setLdap(mixed $ldap): void
    {
        $this->ldap = $ldap;
    }

    public function getRdn(): mixed
    {
        return $this->rdn;
    }

    public function setRdn(mixed $rdn): void
    {
        $this->rdn = $rdn;
    }

    public function getBindDn(): mixed
    {
        return $this->bindDn;
    }

    public function setBindDn(mixed $bindDn): void
    {
        $this->bindDn = $bindDn;
    }

    public function getPassword(): mixed
    {
        return $this->password;
    }

    public function setPassword(mixed $password): void
    {
        $this->password = $password;
    }

    public function getBindType(): mixed
    {
        return $this->bindType;
    }

    public function setBindType(mixed $bindType): void
    {
        $this->bindType = $bindType;
    }

    public function getObjectClass(): mixed
    {
        return $this->objectClass;
    }

    public function setObjectClass(mixed $objectClass): void
    {
        $this->objectClass = $objectClass;
    }

    public function createLDAP(): Ldap
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

    public function getFilter(): mixed
    {
        return $this->filter;
    }

    public function setFilter(mixed $filter): void
    {
        $this->filter = $filter;
    }


    /**
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
     * @return \Symfony\Component\Ldap\Entry[]
     */
    public function retrieveUser(): array
    {

        $options = [
            'scope' => $this->scope,
        ];

        $query = $this->ldap->query($this->userDn, $this->buildObjectClass(), $options);
        $user = $query->execute();
        return $user->toArray();
    }


    /**
     * @return \Symfony\Component\Ldap\Entry[]
     */
    public function retrieveDeputies(): array
    {

        $options = [
            'scope' => $this->scope,
        ];

        $query = $this->ldap->query($this->LDAP_DEPUTY_GROUP_DN, $this->buildObjectClassDeputy(), $options);
        $user = $query->execute();
        return $user->toArray();
    }

    public function getDryRun(): mixed
    {
        return $this->dryRun;
    }

    public function setDryRun(mixed $dryRun): void
    {
        $this->dryRun = $dryRun;
    }

    public function getLDAPDEPUTYGROUPOBJECTCLASS(): mixed
    {
        return $this->LDAP_DEPUTY_GROUP_OBJECTCLASS;
    }

    public function setLDAPDEPUTYGROUPOBJECTCLASS(mixed $LDAP_DEPUTY_GROUP_OBJECTCLASS): void
    {
        $this->LDAP_DEPUTY_GROUP_OBJECTCLASS = $LDAP_DEPUTY_GROUP_OBJECTCLASS;
    }

    public function getLDAPDEPUTYGROUPDN(): mixed
    {
        return $this->LDAP_DEPUTY_GROUP_DN;
    }

    public function setLDAPDEPUTYGROUPDN(mixed $LDAP_DEPUTY_GROUP_DN): void
    {
        $this->LDAP_DEPUTY_GROUP_DN = $LDAP_DEPUTY_GROUP_DN;
    }

    public function getLDAPDEPUTYGROUPLEADER(): mixed
    {
        return $this->LDAP_DEPUTY_GROUP_LEADER;
    }

    public function setLDAPDEPUTYGROUPLEADER(mixed $LDAP_DEPUTY_GROUP_LEADER): void
    {
        $this->LDAP_DEPUTY_GROUP_LEADER = $LDAP_DEPUTY_GROUP_LEADER;
    }

    public function getLDAPDEPUTYGROUPMEMBERS(): mixed
    {
        return $this->LDAP_DEPUTY_GROUP_MEMBERS;
    }

    public function setLDAPDEPUTYGROUPMEMBERS(mixed $LDAP_DEPUTY_GROUP_MEMBERS): void
    {
        $this->LDAP_DEPUTY_GROUP_MEMBERS = $LDAP_DEPUTY_GROUP_MEMBERS;
    }

    public function getLDAPDEPUTYGROUPFILTER(): mixed
    {
        return $this->LDAP_DEPUTY_GROUP_FILTER;
    }

    public function setLDAPDEPUTYGROUPFILTER(mixed $LDAP_DEPUTY_GROUP_FILTER): void
    {
        $this->LDAP_DEPUTY_GROUP_FILTER = $LDAP_DEPUTY_GROUP_FILTER;
    }

    public function isHealthy(): bool
    {
        return $this->isHealthy;
    }

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
