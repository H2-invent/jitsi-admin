<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:migrateToAdressbook')]
class MigrateToAdressbookCommand extends Command
{
    protected $em;
    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('This command collects all rooms which are moderator and puts the participants to the adressbook. This is only used when migrating from very old version.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->em->getRepository(User::class)->findAll();
        $counterUser = 0;
        $counterCOnnections = 0;
        foreach ($users as $user) {
            $rooms = $user->getRoomModerator();
            $counterUser++;
            foreach ($rooms as $room) {
                foreach ($room->getUser() as $participant) {
                    if ($participant != $user) {
                        $counterCOnnections++;
                        $user->addAddressbook($participant);
                        $this->em->persist($user);
                    }
                }
            }
            $this->em->flush();
        }

        $io->success('You genereated ' . $counterCOnnections . ' Adressentries with ' . $counterUser . ' Users');

        return Command::SUCCESS;
    }
}
