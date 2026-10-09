<?php

namespace App\Command;

use App\Service\ProvisionerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:provisioner:cleanup',
    description: 'This command checks for provisioned servers that can be deleted and sends requests to the provisioner to do so.',
)]
class ProvisionerCleanupCommand extends Command
{
    public function __construct(
        private ProvisionerService $provisionerService
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->provisionerService->cleanupUnusedProvisionedServers();

        if ($count > 0) {
            $io->success("Sent {$count} provisioner deletion requests");
        }

        return Command::SUCCESS;
    }
}
