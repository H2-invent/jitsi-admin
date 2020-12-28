<?php

namespace App\Command;

use App\Entity\AuditTomZiele;
use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateDefaultTeamCommand extends Command
{
    protected static $defaultName = 'app:migrate:defaultTeam';
    private $em;

    public function __construct(string $name = null, EntityManagerInterface $entityManager)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('This command sets all Default Teams in the Config Databases from 1 to null');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $team = $this->em->getRepository(Team::class)->find(1);
        $auditZiele = $this->em->getRepository(AuditTomZiele::class)->findBy(array('team' => $team));

        foreach ($auditZiele as $ziel) {
            $ziel->setTeam(null);
            $ziel->setActiv(true);
            $this->em->persist($ziel);
        }

        $this->em->flush();
        $io->success('All Defaul Values have been transformed');

        return 0;
    }
}
