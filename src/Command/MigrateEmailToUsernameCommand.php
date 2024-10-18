<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:migrate:emailToUsername', 'This command migrates emails to username. This is important when the email should be used in the JItsi-admin as usernmae but the prefered_username in the keycloak was not set and the emails are different to the username (F.ex. in LDAP)')]
class MigrateEmailToUsernameCommand extends Command
{
    private $em;
    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->em->getRepository(User::class)->findAll();
        $counter = 0;
        foreach ($users as $data) {
            $data->setUsername($data->getEmail());
            $this->em->persist($data);
            $counter++;
        }
        $this->em->flush();
        $io->success(sprintf('We transform %d User', $counter));

        return Command::SUCCESS;
    }
}
