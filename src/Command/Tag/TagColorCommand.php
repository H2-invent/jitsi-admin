<?php

namespace App\Command\Tag;

use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:tag:color', 'Add a short description for your command')]
class TagColorCommand extends Command
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
            ->addArgument('tagId', InputArgument::OPTIONAL, 'This is the Id of the tag');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tagId = $input->getArgument('tagId');
        if ($tagId) {
            $io->note(sprintf('You passed the ID: %s', $tagId));
        }

        $tag = $this->em->getRepository(Tag::class)->find($tagId);
        if (!$tag) {
            $io->error('Tag does not exist');
            return Command::FAILURE;
        }
        $fontcolorQ = new Question('Enter the font color (ex #790619)', $tag->getColor() ? $tag->getColor() : '#790619');
        $tag->setColor($io->askQuestion($fontcolorQ));

        $backgroundcolorQ = new Question('Enter the background color (ex #fdd8de)', $tag->getBackgroundColor() ? $tag->getBackgroundColor() : '#fdd8de');
        $tag->setBackgroundColor($io->askQuestion($backgroundcolorQ));

        $this->em->persist($tag);
        $this->em->flush();
        $io->success(sprintf('Font color of %s is now set to %s and backgroundcolor is set to %s', $tag->getTitle(), $tag->getColor(), $tag->getBackgroundColor()));

        return Command::SUCCESS;
    }
}
