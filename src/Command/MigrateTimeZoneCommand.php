<?php

namespace App\Command;

use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateTimeZoneCommand extends Command
{
    protected static $defaultName = 'app:migrateTimeZone';
    protected static $defaultDescription = 'Add a short description for your command';
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
            }
            if ($data->getEnddate()) {
                $dateEnd = new \DateTime($data->getEnddate()->format('Y-m-d H:i:s'), $timezone);
                $data->setEndDateUtc($dateEnd->setTimezone(new \DateTimeZone('utc')));
            }
            $this->em->persist($data);
        }
        $this->em->flush();
        return Command::SUCCESS;
    }
}
