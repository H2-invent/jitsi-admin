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

class TagDisableCommand extends Command
{
    protected static $defaultName = 'app:tag:disable';
    protected static $defaultDescription = 'Enter the ID of  the Tag you wish to disable. Check out with app:tag:list';
    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }
    protected function configure(): void
    {
        $this
            ->addArgument('tagId', InputArgument::OPTIONAL, 'Argument description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tagId = $input->getArgument('tagId');

        if ($tagId) {
            $io->note(sprintf('You passed the ID: %s', $tagId));
        }

        $tag = $this->em->getRepository(Tag::class)->find($tagId);
        if (!$tag){
            $io->error('Tag does not exist');
            return  Command::FAILURE;
        }
        $tag->setDisabled(true);
        $this->em->persist($tag);
        $this->em->flush();
        $io->success(sprintf('Tag %s is DISABLED', $tag->getTitle()));

        return Command::SUCCESS;
    }
}
