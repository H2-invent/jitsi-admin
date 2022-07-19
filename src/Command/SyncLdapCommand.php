<?php

namespace App\Command;

use App\dataType\LdapType;
use App\Entity\User;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapUserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Exception\NotBoundException;
use Symfony\Component\Ldap\Ldap;

class SyncLdapCommand extends Command
{
    protected static $defaultName = 'app:ldap:sync';
    protected static $defaultDescription = 'This commands syncs a ldap server with users database';
    private $paramterBag;
    private $ldapService;
    private $ldapUserService;
    private $LDAP;
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
    public function __construct(LdapUserService $ldapUserService,  ParameterBagInterface $parameterBag, LdapService $ldapService, string $name = null)
    {
        parent::__construct($name);
        $this->paramterBag = $parameterBag;
        $this->ldapService = $ldapService;
        $this->ldapUserService = $ldapUserService;
        $this->LDAP = array();
        $this->URL = explode(';', $this->paramterBag->get('ldap_url'));
        $this->LOGIN = explode(';', $this->paramterBag->get('ldap_bind_dn'));
        $this->PASSWORD = explode(';', $this->paramterBag->get('ldap_password'));
        $this->USERDN = explode(';', $this->paramterBag->get('ldap_user_dn'));
        $this->SCOPE = explode(';', $this->paramterBag->get('ldap_search_scope'));
        $this->OBJECTCLASSES = explode(';', $this->paramterBag->get('ldap_user_object_classes'));
        $this->USERNAMEATTRIBUTE = explode(';', $this->paramterBag->get('ldap_userName_attribute'));
        $this->RDN = explode(',', $this->paramterBag->get('ldap_rdn_ldap_attribute'));
        $this->BINDTYPE = explode(',', $this->paramterBag->get('ldap_bind_type'));
        $this->LDAPSERVERID = explode(',', $parameterBag->get('ldap_server_individualName'));
        $this->LDAPFILTER = explode(';', $parameterBag->get('ldap_filter'));

        $tmp = explode(';', $this->paramterBag->get('ldap_attribute_mapper'));
        foreach ($tmp as $data) {
            $this->MAPPER[] = json_decode($data, true);
        }
        $tmp = explode(';', $this->paramterBag->get('ldap_special_Fields'));
        foreach ($tmp as $data) {
            $this->LDAP_SPECIALFIELD[] = json_decode($data, true);
        }
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Activate Dry-Run. Not writing into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryrun = $input->getOption('dry-run');
        if ($dryrun){
            $io->info('Dryrun is activated. No databases changes are made');
        }

        $count = 0;
        $result = array();
        $io->info('We test the all LDAP connections: ');
        $error = false;
        if (sizeof($this->URL) > 0) {
            foreach ($this->URL as $data) {
                $ldap = new LdapType($this->ldapService);
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
                $ldap->setFilter($this->LDAPFILTER[$count]);
                $io->info('Try to connect to: ' . $data);
                try {
                    $ldap->createLDAP();
                    $io->success('Sucessfully connect to ' . $ldap->getUrl());
                } catch (\Exception $exception) {
                    $error = true;
                    $io->error($exception->getMessage());
                    return Command::FAILURE;
                }
                $this->LDAP[] = $ldap;
                $count++;
            }
        }

        $numberUsers = 0;
        if (sizeof($this->LDAP) > 0) {
            foreach ($this->LDAP as $data) {

                $resTmp = null;
                try {
                    $resTmp = $this->ldapService->fetchLdap($data,$dryrun);
                } catch (LdapException $e) {
                    $error = true;
                    $io->error('Fehler in LDAP: ' . $ldap->getUrl());
                    $io->error('Fehler: ' . $e->getMessage());

                } catch (NotBoundException $e) {
                    $error = true;
                    $io->error('Fehler in LDAP-Bound: ' . $ldap->getUrl());
                    $io->error('Fehler: ' . $e->getMessage());
                }

                if ($resTmp !== null) {
                    $result[] = $resTmp;
                }

                $table = new Table($output);
                $table->setHeaders(['email', 'uid', 'dn', 'rdn']);
                $table->setHeaderTitle($ldap->getUrl());
                $table->setStyle('borderless');
                if(is_array($resTmp['user'])) {
                    foreach ($resTmp['user'] as $data2) {
                        $numberUsers++;
                        $table->addRow([$data2->getEmail(), $data2->getUserName(), $data2->getLdapUserProperties()->getLdapDn(), $data2->getLdapUserProperties()->getRdn()]);
                    }
                }
                $table->render();
            }

        }

        $io->info('We found # users: ' . $numberUsers);
        if ($error == false) {
            $io->success('All LDAPS could be synced correctly');
            return Command::SUCCESS;
        } else {
            $io->error('There was an error. Check the output above');
            return Command::FAILURE;
        }

    }
}
