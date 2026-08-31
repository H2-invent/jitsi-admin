<?php

namespace App\Command\Tag;

use App\Entity\Rooms;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:tag:addToAll', 'Add a short description for your command')]
class TagAddToAllCommand extends Command
{
    private const BATCH_SIZE = 500;

    private EntityManagerInterface $em;
    public function __construct(EntityManagerInterface $entityManager, ?string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tags = $this->em->getRepository(Tag::class)->findBy(['disabled' => false], ['priority' => 'ASC']);

        foreach ($tags as $data) {
            if (!$data->getDisabled()) {
                $io->writeln(sprintf('%d [X] %s Prio: %d', $data->getId(), $data->getTitle(), $data->getPriority()));
            } else {
                $io->writeln(sprintf('%d [ ] %s Prio: %d', $data->getId(), $data->getTitle(), $data->getPriority()));
            }
        }
        $fontcolorQ = new Question('Choose the tag id you want to add to all rooms without a tag', $tags[0]->getId());

        $choose = $io->askQuestion($fontcolorQ);
        $tag = $this->em->getRepository(Tag::class)->find($choose);
        if (!$tag) {
            $io->error('No Tag found');
            return Command::FAILURE;
        }

        $roomCount = (int)$this->em->getRepository(Rooms::class)
            ->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.tag IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
        $progressBar = new ProgressBar($output, $roomCount);
        $progressBar->start();
        $tagId = $tag->getId();
        $lastId = 0;

        while (true) {
            $rooms = $this->em->getRepository(Rooms::class)
                ->createQueryBuilder('r')
                ->where('r.id > :lastId')
                ->andWhere('r.tag IS NULL')
                ->orderBy('r.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($rooms) === 0) {
                break;
            }

            $tag = $this->em->getRepository(Tag::class)->find($tagId);
            foreach ($rooms as $room) {
                $lastId = $room->getId();
                $room->setTag($tag);
                $this->em->persist($room);
                $progressBar->advance(1);
            }

            $this->em->flush();
            $this->em->clear();
        }

        $progressBar->finish();
        $io->success('This are all your tags');
        return Command::SUCCESS;
    }
}
