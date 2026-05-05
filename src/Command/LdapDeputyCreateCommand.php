<?php

namespace App\Command;

use App\Service\Deputy\DebutyLdapService;
use App\Service\ldap\LdapService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
    public function __construct(
        private LdapService       $ldapService,
        private DebutyLdapService $debutyLdapService,
        string                    $name = null
    )
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Not writing to the database. Use it to check the connetion');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryrun = $input->getOption('dry-run');
        if ($dryrun) {
            $io->info('Dryrun is activated. No databases changes are made');
        }

        $this->ldapService->initLdap();
        $this->ldapService->testLdap(io: $io);
        $this->debutyLdapService->cleanDeputies($dryrun);
        $this->ldapService->setDeputies($this->ldapService->fetchDeputies());


        $io->success('We connect all LDAP Deputies');

        return Command::SUCCESS;
    }
}
