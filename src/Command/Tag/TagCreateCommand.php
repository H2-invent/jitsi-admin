<?php

namespace App\Command\Tag;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class TagCreateCommand extends Command
{
    protected static $defaultName = 'app:tag:create';
    protected static $defaultDescription = 'Add a short description for your command';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager, private TagRepository $tagRepository, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('title', InputArgument::OPTIONAL, 'Enter the Tag Title here')
            ->addArgument('prio', InputArgument::OPTIONAL, 'Enter the Tag Title here')
            ->addArgument('fontColor', InputArgument::OPTIONAL, 'Enter the Tag Title here')
            ->addArgument('bgcolor', InputArgument::OPTIONAL, 'Enter the Tag Title here');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $title = $input->getArgument('title');
        $tag = new Tag();
        if ($title) {
            $io->note(sprintf('You passed the title for the tag: %s', $title));
            $tagOld = $this->tagRepository->findOneBy(array('title' => $title));
            if ($tagOld) {
                $io->error('The Tag is already defined');
                return Command::FAILURE;
            }
            $tag->setDisabled(false);
        } else {
            $titleQ = new Question('Enter the Tag Name: ', 'Demo Tag');
            $title = $io->askQuestion($titleQ);
            $disableQ = new ConfirmationQuestion('Do you want to DISABLE the Tag', false);
            $tag->setDisabled($io->askQuestion($disableQ));
        }

        $tag->setTitle($title);

        $prio = $input->getArgument('prio');

        if ($prio) {
            $io->note(sprintf('You passed the priority: %d', $prio));
        } else {
            $prioQ = new Question('Enter the Priority (The Lowest will be shown first and is the default)', 0);
            $prio = intval($io->askQuestion($prioQ));
        }

        $tag->setPriority(priority: $prio);

        $fontcolor = $input->getArgument('fontColor');
        if ($fontcolor) {
            $io->note(sprintf('You passed the Fontcolor: %s', $fontcolor));
        } else {
            $fontcolorQ = new Question('Enter the font color (ex #790619)', $tag->getColor() ? $tag->getColor() : '#790619');
            $fontcolor = $io->askQuestion($fontcolorQ);
        }
        $tag->setColor($fontcolor);


        $bgcolor = $input->getArgument('bgcolor');
        if ($bgcolor) {
            $io->note(sprintf('You passed the backgroundcolor: %s', $bgcolor));
        } else {
            $backgroundcolorQ = new Question('Enter the background color (ex #fdd8de)', $tag->getBackgroundColor() ? $tag->getBackgroundColor() : '#fdd8de');
            $bgcolor = $io->askQuestion($backgroundcolorQ);
        }

        $tag->setBackgroundColor($bgcolor);

        $this->em->persist($tag);
        $this->em->flush();


        $io->success(sprintf('The Tag %s was added sucessfully', $title));

        return Command::SUCCESS;
    }
}
