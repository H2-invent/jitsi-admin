<?php

namespace App\Command;

use App\Service\ReminderService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronSendReminderCommand extends Command
{
    protected static $defaultName = 'app:cron:sendReminder';
    private $reminderService;
    public function __construct(ReminderService $reminderService, string $name = null)
    {
        parent::__construct($name);
        $this->reminderService = $reminderService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send a reminder to all users which are in a room in the next 10 min')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $res = $this->reminderService->sendReminder();

        $io->writeln('Hinweis: '. $res['hinweis']);
        $io->writeln('Konferenzen: '.$res['Konferenzen']);
        $io->writeln('Emails: '.$res['Emails']);
        $io->writeln('Datum: '.(new \DateTime())->format('d.m.Y'));
        $io->writeln('Zeit: '.(new \DateTime())->format('H:i'));
        if(!$res['error']){
            $io->success('Erfolgreich versandt');
            return Command::SUCCESS;
        }else{
            $io->error('Fehler');
            return Command::FAILURE;
        }
    }
}
