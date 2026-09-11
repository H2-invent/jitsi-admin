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
    private const BATCH_SIZE = 500;

    protected $em;
    public function __construct(EntityManagerInterface $entityManager, ?string $name = null)
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
        $counterUser = 0;
        $counterConnections = 0;
        $lastId = 0;

        while (true) {
            $users = $this->em->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.id > :lastId')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($users) === 0) {
                break;
            }

            foreach ($users as $user) {
                $lastId = $user->getId();
                $rooms = $user->getRoomModerator();
                $counterUser++;
                foreach ($rooms as $room) {
                    foreach ($room->getUser() as $participant) {
                        if ($participant != $user) {
                            $counterConnections++;
                            $user->addAddressbook($participant);
                            $this->em->persist($user);
                        }
                    }
                }
            }

            $this->em->flush();
            $this->em->clear();
        }

        $io->success('You generated ' . $counterConnections . ' Address entries with ' . $counterUser . ' Users');

        return Command::SUCCESS;
    }
}
