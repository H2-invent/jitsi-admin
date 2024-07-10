<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:migration:addUsernameSameEmail', 'This command finds empts username and sets the user = email')]
class MigrationAddUsernameSameEmailCommand extends Command
{
    protected $em;
    public function __construct(EntityManagerInterface $entityManager, $name = null)
    {
        $this->em = $entityManager;
        parent::__construct($name);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = $this->em->getRepository(User::class)->findBy(['username' => null]);
        foreach ($user as $data) {
            $data->setUsername($data->getEmail());
            $this->em->persist($data);
        }
        $this->em->flush();
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
