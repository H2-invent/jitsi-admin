<?php

namespace App\Command;

use App\Service\MailerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'app:check:theme:validDate',
    description: 'Add a short description for your command',
)]
class CheckThemeValidDateCommand extends Command
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private MailerService         $mailerService,
        string                        $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('maxTime', InputArgument::OPTIONAL, 'Argument description', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = 0;
        $io = new SymfonyStyle($input, $output);
        $maxTime = $input->getArgument('maxTime');
        $finder = new Finder();
        $finder->files()->in($this->parameterBag->get('kernel.project_dir') . '/theme/')->name('*theme.json.signed');
        $arr = iterator_to_array($finder);
        foreach ($arr as $path) {
            $theme = json_decode($path->getContents(), true)['entry'];
            $validUntil = $theme['validUntil'];
            $contact = [
                'entwicklung@h2-invent.com',
                'buchhaltung@h2-invent.com'
            ];
            $contactEmail = 'Not in Theme';

            if (isset($theme['contactEmail'])) {
                $contactEmail = $theme['contactEmail'];
                $contact = array_merge($contact, explode(',', $contactEmail));
            }

            if ($validUntil) {
                $validDate = new \DateTime($validUntil);
                $now = new \DateTime();
                $daysDifff = intval(($now->diff($validDate))->format('%R%a'));
                if ($daysDifff < $maxTime && $daysDifff > 0) {

                    $filename = $path->getFileName();
                    $subject = sprintf('[Jitsi-admin theme expiring] Expiring Theme for URL: %s', $filename);
                    $message = sprintf('Your Theme for your jitsi-admin is expiring in %d days.<br> Your Theme file is named: %s<br>Your contact mail address is: %s', $daysDifff, $filename, $contactEmail);
                    $this->mailerService->sendPlainMail(implode(',', $contact), $subject, $message);
                    $count++;
                }
            }
        }

        $io->success(sprintf('We send %d emails with expiring Themes', $count));

        return Command::SUCCESS;
    }
}

