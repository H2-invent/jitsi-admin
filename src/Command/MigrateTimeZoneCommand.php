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
        $rooms = $this->em->getRepository(Rooms::class)->findAll();

        foreach ($rooms as $data) {
            $timezone = $data->getTimeZone() ? new \DateTimeZone($data->getTimeZone()) : null;

            if ($data->getStart()) {
                $dateStart = new \DateTime($data->getStart()->format('Y-m-d H:i:s'), $timezone);
                $data->setStartUtc($dateStart->setTimezone(new \DateTimeZone('utc')));
                $data->setStartTimestamp((new \DateTime($data->getStart()->format('Y-m-d H:i:s'), $timezone))->getTimestamp());
            }
            if ($data->getEnddate()) {
                $dateEnd = new \DateTime($data->getEnddate()->format('Y-m-d H:i:s'), $timezone);
                $data->setEndDateUtc($dateEnd->setTimezone(new \DateTimeZone('utc')));
                $data->setEndTimestamp((new \DateTime($data->getEnddate()->format('Y-m-d H:i:s'), $timezone))->getTimestamp());
            }
            $this->em->persist($data);
        }
        $this->em->flush();
        return Command::SUCCESS;
    }
}
