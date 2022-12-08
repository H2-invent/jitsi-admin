<?php

namespace App\Command;

use App\Service\ldap\LdapService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ldap:deputy:create',
    description: 'Creates Deputys by an LDAP connection',
)]
class LdapDeputyCreateCommand extends Command
{

    public function __construct(private LdapService $ldapService, string $name = null)
    {
        parent::__construct($name);

    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'This command is not writing to the database. Use it to check the connetion')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryrun = $input->getOption('dry-run');
        if ($dryrun){
            $io->info('Dryrun is activated. No databases changes are made');
        }

        //todo build
        $this->ldapService->initLdap($io);
        foreach ($this->ldapService->getLdaps() as $data) {

        }


        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
