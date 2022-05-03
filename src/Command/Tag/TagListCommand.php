<?php

namespace App\Command\Tag;

use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TagListCommand extends Command
{
    protected static $defaultName = 'app:tag:list';
    protected static $defaultDescription = 'Add a short description for your command';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tags = $this->em->getRepository(Tag::class)->findBy(array(),array('priority'=>'ASC'));

        foreach ($tags as $data) {
            if (!$data->getDisabled()) {
                $io->writeln(sprintf('%d [X] %s Prio: %d', $data->getId(), $data->getTitle(), $data->getPriority()));
            } else {
                $io->writeln(sprintf('%d [ ] %s Prio: %d', $data->getId(), $data->getTitle(), $data->getPriority()));
            }
        }

        $io->success('This are all your tags');

        return Command::SUCCESS;
    }
}
