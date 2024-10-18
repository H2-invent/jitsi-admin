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

#[\Symfony\Component\Console\Attribute\AsCommand('app:tag:Priority', 'Add a short description for your command')]
class TagPriorityCommand extends Command
{
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('tagId', InputArgument::OPTIONAL, 'This is the Id of the tag')
            ->addArgument('prio', InputArgument::OPTIONAL, 'This is the new Priority of the tag');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tagId = $input->getArgument('tagId');
        $prio = $input->getArgument('prio');
        if ($tagId) {
            $io->note(sprintf('You passed the ID: %s', $tagId));
        }

        $tag = $this->em->getRepository(Tag::class)->find($tagId);
        if (!$tag) {
            $io->error('Tag does not exist');
            return Command::FAILURE;
        }
        $tag->setPriority($prio);
        $this->em->persist($tag);
        $this->em->flush();
        $io->success(sprintf('Priority of %s is now set to %d', $tag->getTitle(), $tag->getPriority()));

        return Command::SUCCESS;
    }
}
