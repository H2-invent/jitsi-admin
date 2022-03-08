<?php

namespace App\Command;

use App\Service\CleanupLobbyService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanUpLobbyCommand extends Command
{
    protected static $defaultName = 'app:lobby:cleanUp';
    protected static $defaultDescription = 'Add a short description for your command';
    private $cleanUp;
    public function __construct(CleanupLobbyService $cleanupLobbyService, string $name = null)
    {
        parent::__construct($name);
        $this->cleanUp = $cleanupLobbyService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('We delete all Lobbyusers which are older then %d hours', $arg1));
        }

        $res = $this->cleanUp->cleanUp($arg1);
        $io->success(sprintf('We deleted %d lobby users',sizeof($res)));

        return Command::SUCCESS;
    }
}
