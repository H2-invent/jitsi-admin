<?php

namespace App\Command;

use App\Service\caller\CallerPrepareService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CallerPrepareCommand extends Command
{
    protected static $defaultName = 'app:caller:prepare';
    protected static $defaultDescription = 'This command adds CallerIds if there are no caller Ids-added and remove old CallerIds';
    private $em;
    private $callerPrepareService;
    public function __construct(CallerPrepareService $callerPrepareService, EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em= $entityManager;
        $this->callerPrepareService = $callerPrepareService;
    }

    protected function configure(): void
    {

    }

    protected function execute( InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->callerPrepareService->prepareCallerId();
        $this->callerPrepareService->createUserCallerId();
        $io->success('We added to all Rooms which had no caller-Id a caller-Id');

        return Command::SUCCESS;
    }
}
