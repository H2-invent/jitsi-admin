<?php

namespace App\Service\ldap;

use App\dataType\LdapType;
use App\Entity\Deputy;
use App\Entity\LdapUserProperties;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Ldap\Entry;

class LdapService
{
    private $ldapUserService;
    private $em;
    /**
     * @var LdapType[]
     */
    private $ldaps;
    private $URL;
    private $LOGIN;
    private $PASSWORD;
    private $USERDN;
    private $SCOPE;
    private $OBJECTCLASSES;
    private $USERNAMEATTRIBUTE;
    private $MAPPER;
    private $RDN;
    private $BINDTYPE;
    private $LDAPSERVERID;
    private $LDAP_SPECIALFIELD;
    private $LDAPFILTER;
    private $LDAP_DEPUTY_GROUP_OBJECTCLASS;
    private $LDAP_DEPUTY_GROUP_DN;
    private $LDAP_DEPUTY_GROUP_LEADER;
    private $LDAP_DEPUTY_GROUP_MEMBERS;
    private $LDAP_DEPUTY_GROUP_FILTER;

    private $LDAP_IS_SIP_VIDEO;

    public function __construct(
        LdapUserService               $ldapUserService,
        EntityManagerInterface        $entityManager,
        private ParameterBagInterface $parameterBag,
        private LoggerInterface       $logger,)
    {
        $this->ldapUserService = $ldapUserService;
        $this->em = $entityManager;
        $this->ldaps = [];
    }

    /**
     * @return bool|int
     */
    public function readLdapConfig(): bool|int
    {
        try {
            $this->URL = explode(';', $this->parameterBag->get('ldap_url'));
            $this->LOGIN = explode(';', $this->parameterBag->get('ldap_bind_dn'));
            $this->PASSWORD = explode(';', $this->parameterBag->get('ldap_password'));
            $this->USERDN = explode(';', $this->parameterBag->get('ldap_user_dn'));
            $this->SCOPE = explode(';', $this->parameterBag->get('ldap_search_scope'));
            $this->OBJECTCLASSES = explode(';', $this->parameterBag->get('ldap_user_object_classes'));
            $this->USERNAMEATTRIBUTE = explode(';', $this->parameterBag->get('ldap_userName_attribute'));
            $this->RDN = explode(',', $this->parameterBag->get('ldap_rdn_ldap_attribute'));
            $this->BINDTYPE = explode(',', $this->parameterBag->get('ldap_bind_type'));
            $this->LDAPSERVERID = explode(',', $this->parameterBag->get('ldap_server_individualName'));
            $this->LDAPFILTER = explode(';', $this->parameterBag->get('ldap_filter'));
            $this->LDAP_DEPUTY_GROUP_DN = explode(';', $this->parameterBag->get('LDAP_DEPUTY_GROUP_DN'));
            $this->LDAP_DEPUTY_GROUP_LEADER = explode(';', $this->parameterBag->get('LDAP_DEPUTY_GROUP_LEADER'));
            $this->LDAP_DEPUTY_GROUP_MEMBERS = explode(';', $this->parameterBag->get('LDAP_DEPUTY_GROUP_MEMBERS'));
            $this->LDAP_DEPUTY_GROUP_OBJECTCLASS = explode(';', $this->parameterBag->get('LDAP_DEPUTY_GROUP_OBJECTCLASS'));
            $this->LDAP_DEPUTY_GROUP_FILTER = explode(';', $this->parameterBag->get('LDAP_DEPUTY_GROUP_FILTER'));
            $this->LDAP_IS_SIP_VIDEO = explode(';', $this->parameterBag->get('LDAP_IS_SIP_VIDEO'));
            $tmp = explode(';', $this->parameterBag->get('ldap_attribute_mapper'));
            foreach ($tmp as $data) {
                $this->MAPPER[] = json_decode($data, true);
            }
            $tmp = explode(';', $this->parameterBag->get('ldap_special_Fields'));
            foreach ($tmp as $data) {
                $this->LDAP_SPECIALFIELD[] = json_decode($data, true);
            }
            return sizeof($this->URL);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * @return int
     */
    public function createLdapConnections()
    {
        if (sizeof($this->URL) > 0) {
            $count = 0;
            foreach ($this->URL as $data) {
                $ldap = new LdapType();
                $ldap->setBindDn($this->LOGIN[$count]);
                $ldap->setRdn($this->RDN[$count]);
                $ldap->setBindType($this->BINDTYPE[$count]);
                $ldap->setMapper($this->MAPPER[$count]);
                $ldap->setPassword($this->PASSWORD[$count]);
                $ldap->setScope($this->SCOPE[$count]);
                $ldap->setSerVerId($this->LDAPSERVERID[$count]);
                $ldap->setUserNameAttribute($this->USERNAMEATTRIBUTE[$count]);
                $ldap->setUrl($data);
                $ldap->setObjectClass($this->OBJECTCLASSES[$count]);
                $ldap->setUserDn($this->USERDN[$count]);
                $ldap->setSpecialFields($this->LDAP_SPECIALFIELD[$count]);

                $ldap->setFilter($this->LDAPFILTER[$count] !== '' ? $this->LDAPFILTER[$count] : null);
                $ldap->setLDAPDEPUTYGROUPDN($this->LDAP_DEPUTY_GROUP_DN[$count]);
                $ldap->setLDAPDEPUTYGROUPLEADER($this->LDAP_DEPUTY_GROUP_LEADER[$count]);
                $ldap->setLDAPDEPUTYGROUPMEMBERS($this->LDAP_DEPUTY_GROUP_MEMBERS[$count]);
                $ldap->setLDAPDEPUTYGROUPOBJECTCLASS($this->LDAP_DEPUTY_GROUP_OBJECTCLASS[$count]);
                $ldap->setLDAPDEPUTYGROUPFILTER($this->LDAP_DEPUTY_GROUP_FILTER[$count] !== '' ? $this->LDAP_DEPUTY_GROUP_FILTER[$count] : null);
                try {
                    $ldap->setISSIPVIDEO($this->LDAP_IS_SIP_VIDEO[$count] === 'true');
                } catch (\Exception $exception) {

                }

                $duplicate = false;
                foreach ($this->ldaps as $data2) {
                    if ($data2->getSerVerId() == $ldap->getSerVerId()) {
                        $duplicate = true;
                    }
                }
                if (!$duplicate) {
                    $this->ldaps[] = $ldap;
                }

                $count++;
            }

        }


        return sizeof($this->ldaps);
    }

    /**
     * Try to connect all ldaps with the LDAP server to check if all is working
     * @return bool
     */
    public function connectToLdap(?SymfonyStyle $io = null): bool
    {
        foreach ($this->ldaps as $data) {
            if ($io) {
                $io->info('Try to connect to: ' . $data);
            }

            try {
                $data->createLDAP();
                if ($io) {
                    $io->success('Sucessfully connect to ' . $data->getUrl());
                }
            } catch (\Exception $exception) {
                $error = true;
                if ($io) {
                    $io->error($exception->getMessage());
                }

                $this->logger->error($exception->getMessage());
                return false;
            }
        }
        return true;
    }


    /**
     * @param SymfonyStyle $io
     * @return bool
     */
    public function initLdap(?SymfonyStyle $io = null): bool
    {
        $this->readLdapConfig();
        $this->createLdapConnections();
        return true;
    }

    /**
     * @param SymfonyStyle $io
     * @return bool
     */
    public function testLdap(?SymfonyStyle $io = null): bool
    {
        return $this->connectToLdap($io);
    }


    /**
     * @param LdapType $ldap
     * @return array
     * @throws \Exception
     */
    public function fetchLdap(LdapType $ldap, $dryRun = false)
    {

        $user = null;

        try {
            $userLdap = $ldap->retrieveUser(); //Here we fetch all coresponding users from the LDAP
            foreach ($userLdap as $u) {// Here we itterate over the user from user
                $user[] = $this->ldapUserService->retrieveUserfromDatabasefromUserNameAttribute($u, $ldap, $dryRun);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return ['ldap' => $ldap, 'user' => $user];
    }

    public function fetchDeputies()
    {
        $res = [];
        foreach ($this->ldaps as $data) {
            if ($data->isHealthy()) {
                $res = array_merge($res, $data->retrieveDeputies());
            }
        }
        $res = array_unique($res, SORT_REGULAR);
        return $res;
    }

    /**
     * This Function set the deputyis from the ldap.
     * An array with LDAP elements is send to tis function. this element is then split into elements and we select the
     * attributes which are configured i nthe env.
     * @param Entry[] $entrys
     * @return void
     */
    public function setDeputies($entrys, $dryrun = false)
    {
        foreach ($entrys as $data) {
            foreach ($this->ldaps as $ldap) {
                $members = $data->getAttribute($ldap->getLDAPDEPUTYGROUPMEMBERS());
                $leader = $data->getAttribute($ldap->getLDAPDEPUTYGROUPLEADER());
                foreach ($leader as $lead) {
                    $l = $this->em->getRepository(LdapUserProperties::class)->findOneBy(['ldapDn' => $lead, 'ldapNumber' => $ldap->getSerVerId()]);
                    if ($l) {
                        $l = $l->getUser();
                        foreach ($members as $mem) {
                            $mem = $this->em->getRepository(LdapUserProperties::class)->findOneBy(['ldapDn' => $mem, 'ldapNumber' => $ldap->getSerVerId()]);
                            $deputy = $this->em->getRepository(Deputy::class)->findOneBy(['manager' => $l, 'deputy' => $mem->getUser()]);
                            if (!$deputy) {
                                $deputy = new Deputy();
                                $deputy->setCreatedAt(new \DateTime())
                                    ->setDeputy($mem->getUser())
                                    ->setManager($l);
                            }

                            $deputy->setIsFromLdap(true);
                            $this->em->persist($deputy);
                        }
                    }
                }
            }
        }
        if (!$dryrun) {
            $this->em->flush();
        } else {
            $this->em->clear();
        }
    }


    /**
     * @return LdapType[]|array
     */
    public function getLdaps(): array
    {
        return $this->ldaps;
    }

    /**
     * @param LdapType[]|array $ldaps
     */
    public function setLdaps(array $ldaps): void
    {
        $this->ldaps = $ldaps;
    }

    public function cleanUpLdapUsers()
    {
        foreach ($this->ldaps as $data) {
            $this->ldapUserService->syncDeletedUser($data);
        }
    }
}
