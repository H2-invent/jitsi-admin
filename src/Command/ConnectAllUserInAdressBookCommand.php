<?php

namespace App\Command;

use App\Service\ldap\LdapUserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:ldap:ConnectAllUserInAdressBook', 'This Command adds all LDAP Users in the Adressbook of each other. This command only works after app:ldap:sync')]
class ConnectAllUserInAdressBookCommand extends Command
{
    private $ldapUSerService;
    public function __construct(LdapUserService $ldapUserService, string $name = null)
    {
        parent::__construct($name);
        $this->ldapUSerService = $ldapUserService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $count = sizeof($this->ldapUSerService->connectUserwithAllUSersInAdressbock());
            $this->ldapUSerService->cleanUpAdressbook();
            $io->success(sprintf('We connect %d user in the adressbook', $count));
            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $io->error('Error. Connecting all users failed');
            return Command::FAILURE;
        }
    }
}
