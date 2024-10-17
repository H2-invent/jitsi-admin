<?php

namespace App\Command;

use App\Service\ReminderService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:cron:sendReminder')]
class CronSendReminderCommand extends Command
{
    private $reminderService;

    public function __construct(ReminderService $reminderService, string $name = null)
    {
        parent::__construct($name);
        $this->reminderService = $reminderService;
    }

    protected function configure():void
    {

        $this
            ->addOption('host_url', 'u', InputOption::VALUE_OPTIONAL, 'Set the server-domain from which you want to send the reminder. this is a komma seperated list. Write null to send from a room with host_url null leave blank to send from all host_url')
            ->setDescription('Send a reminder to all users which are in a room in the next 10 min');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filter = null;
        if ($input->getOption('host_url')) {
            $hostUrl = explode(',', $input->getOption('host_url'));
            $io->info('You activate the Host Fiter');
            foreach ($hostUrl as $data) {
                if ($data === 'null') {
                    $filter[] = null;
                    $io->writeln('We select Rooms with an empty hostUrl');
                } else {
                    $filter[] = $data;
                    $io->writeln(sprintf('We select Rooms with %s', $data));
                }
            }
        }
        $res = $this->reminderService->sendReminder($filter);

        $io->writeln('Hinweis: ' . $res['hinweis']);
        $io->writeln('Konferenzen: ' . $res['Konferenzen']);
        $io->writeln('Emails: ' . $res['Emails']);
        $io->writeln('Datum: ' . (new \DateTime())->format('d.m.Y'));
        $io->writeln('Zeit: ' . (new \DateTime())->format('H:i'));
        if (!$res['error']) {
            $io->success('Erfolgreich versandt');
            return Command::SUCCESS;
        } else {
            $io->error('Fehler');
            return Command::FAILURE;
        }
    }
}
