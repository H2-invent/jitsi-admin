<?php

namespace App\Command;

use App\Entity\Rooms;
use App\Service\Summary\SendSummaryViaEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send:summary',
    description: 'Send the summary to all participants',
)]
class SendSummaryCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private SendSummaryViaEmailService $sendSummaryViaEmailService, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('roomid', InputArgument::OPTIONAL, 'Room id to send summary');
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('roomid');
        $room = $this->entityManager->getRepository(Rooms::class)->find($arg1);

        $this->sendSummaryViaEmailService->sendSummaryForRoom($room);
        $io->success(sprintf('We send the summary for %s to %d participants', $room->getName(), sizeof($room->getUser())));

        return Command::SUCCESS;
    }
}
