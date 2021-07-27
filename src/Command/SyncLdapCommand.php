<?php

namespace App\Command;

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
    protected static $defaultName = 'app:sync:ldap';
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
    public function __construct(LdapUserService $ldapUserService, string $name = null, ParameterBagInterface $parameterBag, LdapService $ldapService)
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

        $tmp = explode(';', $this->paramterBag->get('ldap_attribute_mapper'));
        foreach ($tmp as $data) {
            $this->MAPPER[] = json_decode($data,true);
        }
    }

    protected function configure(): void
    {
        $this
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = 0;
        $result = array();
        $io->info('We test the all LDAP connections: ');
        $error = false;
        if (sizeof($this->URL) > 0) {
            foreach ($this->URL as $data) {
                $io->info('Try to connect to: ' . $data);
                try {
                    $ldap = $this->ldapService->createLDAP($data, $this->LOGIN[$count], $this->PASSWORD[$count], $input, $output);
                } catch (InvalidCredentialsException $e) {
                    return Command::FAILURE;
                }
                $this->LDAP[] = $ldap;
                $count++;
            }
        }
        $count = 0;
        if (sizeof($this->LDAP) > 0) {
            foreach ($this->LDAP as $data) {
                $res = $this->ldapService->fetchLdap($data,$this->USERDN[$count],$this->OBJECTCLASSES[$count],$this->SCOPE[$count],$this->MAPPER[$count],$this->URL[$count],$this->USERNAMEATTRIBUTE[$count],$output,$input);
                if ($res !== null){
                    $result = array_merge($res,$result);
                }
                $count++;
            }
        }
        if($this->paramterBag->get('ldap_connect_all_user_addressbook')== 1){
            $this->ldapUserService->connectUserwithAllUSersInAdressbock();
        }
        $io->info('We found # users: ' . sizeof($result));
        if ($error == false) {
            $io->success('All LDAPS could be synced correctly');
            return Command::SUCCESS;
        } else {
            $io->error('There was an error. Check the output above');
            return Command::FAILURE;
        }

    }
}
