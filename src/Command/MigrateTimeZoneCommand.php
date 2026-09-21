<?php

namespace App\Command;

use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:migrateTimeZone', 'This command creates a UTC Time from the local start time. This command ist only one time important when you migrate to version ^0.71.xx')]
class MigrateTimeZoneCommand extends Command
{
    private const BATCH_SIZE = 500;

    private $em;

    public function __construct(EntityManagerInterface $entityManager, ?string $name = null)
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
        $lastId = 0;

        while (true) {
            $rooms = $this->em->getRepository(Rooms::class)
                ->createQueryBuilder('r')
                ->where('r.id > :lastId')
                ->orderBy('r.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($rooms) === 0) {
                break;
            }

            foreach ($rooms as $user) {
                $lastId = $user->getId();
                $timezone = $user->getTimeZone() ? new \DateTimeZone($user->getTimeZone()) : null;

                if ($user->getStart()) {
                    $dateStart = new \DateTime($user->getStart()->format('Y-m-d H:i:s'), $timezone);
                    $user->setStartUtc($dateStart->setTimezone(new \DateTimeZone('utc')));
                    $user->setStartTimestamp((new \DateTime($user->getStart()->format('Y-m-d H:i:s'), $timezone))->getTimestamp());
                }
                if ($user->getEnddate()) {
                    $dateEnd = new \DateTime($user->getEnddate()->format('Y-m-d H:i:s'), $timezone);
                    $user->setEndDateUtc($dateEnd->setTimezone(new \DateTimeZone('utc')));
                    $user->setEndTimestamp((new \DateTime($user->getEnddate()->format('Y-m-d H:i:s'), $timezone))->getTimestamp());
                }
                $this->em->persist($user);
            }

            $this->em->flush();
            $this->em->clear();
        }

        return Command::SUCCESS;
    }
}
