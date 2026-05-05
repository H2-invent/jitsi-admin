<?php

namespace App\Command;

use App\Service\ldap\LdapService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\Exception\NotBoundException;

#[\Symfony\Component\Console\Attribute\AsCommand('app:ldap:sync', 'This commands syncs a ldap server with users database')]
class SyncLdapCommand extends Command
{
    protected static $defaultName = 'app:ldap:sync';
    protected static $defaultDescription = 'This commands syncs a ldap server with users database';

    public function __construct(
        private LdapService $ldapService,
        string              $name = null
    )
    {
        parent::__construct($name);

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
        if ($dryrun) {
            $io->info('Dryrun is activated. No databases changes are made');
        }

        $count = 0;
        $result = [];
        $io->info('We test the all LDAP connections: ');
        $error = false;
        $this->ldapService->initLdap();
        $this->ldapService->testLdap(io: $io);

        $numberUsers = 0;

        foreach ($this->ldapService->getLdaps() as $data) {
            $resTmp = null;
            if ($data->isHealthy()) {
                try {
                    $resTmp = $this->ldapService->fetchLdap($data, $dryrun);
                } catch (LdapException $e) {
                    $error = true;
                    $io->error('Fehler in LDAP: ' . $data->getUrl());
                    $io->error('Fehler: ' . $e->getMessage());
                } catch (NotBoundException $e) {
                    $error = true;
                    $io->error('Fehler in LDAP-Bound: ' . $data->getUrl());
                    $io->error('Fehler: ' . $e->getMessage());
                }

                if ($resTmp !== null) {
                    $result[] = $resTmp;
                }
                $numberUsers += $this->printTable(output: $output, header: $data->getUrl() . ' | ' . $data->getUserDn(), data: $resTmp);

            } else {
                $io->error('This LDAP is unhealty: ' . $data->getUrl());
            }
        }

        if (!$dryrun) {
            $io->info('We cleanup Users which are not in the LDAP anymore');
            $this->ldapService->cleanUpLdapUsers();
        }

        $io->info('We found # users: ' . $numberUsers);
        if ($error === false) {
            $io->success('All LDAPS could be synced correctly');
            return Command::SUCCESS;
        } else {
            $io->error('There was an error. Check the output above');
            return Command::FAILURE;
        }
    }

    private function printTable(OutputInterface $output, $header, array $data)
    {

        $numberUsers = 0;
        $table = new Table($output);
        $table->setHeaderTitle($header);
        $table->setStyle('borderless');
        $table->setHeaders(
            [
                'email',
                'uid',
                'dn',
                'rdn'
            ]
        );

        if (is_array($data['user'])) {
            foreach ($data['user'] as $data2) {
                $numberUsers++;
                $table->addRow(
                    [
                        $data2->getEmail(),
                        $data2->getUserName(),
                        $data2->getLdapUserProperties()->getLdapDn(),
                        $data2->getLdapUserProperties()->getRdn()
                    ]
                );
            }
        }
        $table->render();
        return $numberUsers;
    }
}
