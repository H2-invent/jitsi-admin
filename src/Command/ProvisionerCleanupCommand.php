<?php

namespace App\Command;

use App\Service\Provisioner\ProvisionerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:provisioner:cleanup',
    description: 'This command checks for provisioned servers that can be deleted and sends requests to the provisioner to do so. Also checks for failed provisionings and restarts them.',
)]
class ProvisionerCleanupCommand extends Command
{
    public function __construct(
        private readonly ProvisionerService $provisionerService
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->provisionerService->cleanupFinishedProvisions();
        if ($count > 0) {
            $io->success("Sent {$count} provisioner deletion requests");
        }

        $count = $this->provisionerService->cleanupStuckProvisions();
        if ($count > 0) {
            $io->success("Restarted {$count} stuck provisionings");
        }

        $count = $this->provisionerService->cleanupStuckDeletions();
        if ($count > 0) {
            $io->success("Failed {$count} stuck deletions");
        }

        return Command::SUCCESS;
    }
}
