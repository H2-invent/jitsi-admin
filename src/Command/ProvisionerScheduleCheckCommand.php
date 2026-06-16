<?php

namespace App\Command;

use App\Service\ProvisionerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:provisioner:schedule-check',
    description: 'Checks if any meetings are scheduled within the next X minutes and starts the provisioning ahead of time',
)]
class ProvisionerScheduleCheckCommand extends Command
{
    public function __construct(
        #[Autowire(param: 'provisioner.schedule.minutes_threshold')]
        private int $minutesThreshold,
        private ProvisionerService $provisionerService,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'minutes_threshold',
            InputArgument::OPTIONAL,
            'minutes before scheduled time to start provisioning',
            $this->minutesThreshold,
            [5, 30],
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $minutesThreshold = $input->getArgument('minutes_threshold');

        if (!is_numeric($minutesThreshold) || (int)$minutesThreshold <= 0) {
            $io->error('Only positive integers as argument allowed!');

            return Command::FAILURE;
        }
        $minutesThreshold = (int)$minutesThreshold;

        $count = $this->provisionerService->provisionServerForRoomsStartingIn($minutesThreshold);

        if ($count > 0) {
            $io->success("Sent {$count} provisioning requests");
        }

        return Command::SUCCESS;
    }
}
