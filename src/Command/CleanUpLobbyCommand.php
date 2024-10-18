<?php

namespace App\Command;

use App\Service\CleanupLobbyService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:lobby:cleanUp', 'Enter the max age of Waiting users in the lobby in hours')]
class CleanUpLobbyCommand extends Command
{
    private $cleanUp;

    public function __construct(CleanupLobbyService $cleanupLobbyService, string $name = null)
    {
        parent::__construct($name);
        $this->cleanUp = $cleanupLobbyService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('maxAge', InputArgument::OPTIONAL, 'Enter the max age of Waiting users in the lobby in hours');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('maxAge');
        if ($arg1 === null) {
            $arg1 = 72;
        }
        $io->note(sprintf('We delete all Lobbyusers which are older then %d hours', $arg1));

        $res = $this->cleanUp->cleanUp($arg1);
        $io->success(sprintf('We deleted %d lobby users', sizeof($res)));

        return Command::SUCCESS;
    }
}
