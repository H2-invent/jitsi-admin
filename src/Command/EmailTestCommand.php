<?php

namespace App\Command;

use App\Entity\Server;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:email:test', 'This commands sends an email from a choosen server.')]
class EmailTestCommand extends Command
{
    private MailerService $mailerService;
    private EntityManagerInterface $em;
    protected function configure(): void
    {
        $this
            ->addArgument('serverId', InputArgument::OPTIONAL, 'Server ID from where the amil should be send')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email to where the email schoueld be sent');
    }
    public function __construct(EntityManagerInterface $entityManager, MailerService $mailerService, string $name = null)
    {
        parent::__construct($name);
        $this->mailerService = $mailerService;
        $this->em = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serverId = $input->getArgument('serverId');
        $server = $this->em->getRepository(Server::class)->find($serverId);
        $email = $input->getArgument('email');
        if (!$server) {
            $io->error('Enter a valid Server ID');
            return Command::FAILURE;
        }

        if (!$email) {
            $io->error('Enter an email');
            return Command::FAILURE;
        }
        $user = new User();
        $user->setEmail($email);
        $this->mailerService->sendEmail($user, 'Test-Email from command', sprintf('<h1>This email was send from a command</h1><br><p>Server:%s<br>SMTP-Host:%s<br>Check the From header to make sure the server is correct</p>', $server->getUrl(), $server->getSmtpHost()), $server);


        return Command::SUCCESS;
    }
}
