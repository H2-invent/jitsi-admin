<?php

namespace App\Command;

use App\Entity\AddressGroup;
use App\Entity\User;
use App\Service\IndexGroupsService;
use App\Service\IndexUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:index:user', 'This command reindex the user and the addressbookgroups name')]
class IndexUserCommand extends Command
{
    private const BATCH_SIZE = 500;

    private $em;
    private $indexer;
    private $groupIndexer;
    protected function configure(): void
    {
    }

    public function __construct(EntityManagerInterface $entityManager, IndexUserService $indexUserService, IndexGroupsService $indexGroupsService, ?string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
        $this->indexer = $indexUserService;
        $this->groupIndexer = $indexGroupsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userCount = $this->em->getRepository(User::class)->count([]);
        $progressBar = new ProgressBar($output, $userCount);
        $progressBar->start();
        $lastId = 0;

        while (true) {
            $users = $this->em->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.id > :lastId')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($users) === 0) {
                break;
            }

            foreach ($users as $user) {
                $lastId = $user->getId();
                $progressBar->advance();
                $user->setIndexer($this->indexer->indexUser($user));
                $this->em->persist($user);
            }

            $this->em->flush();
            $this->em->clear();
        }

        $progressBar->finish();
        $io->success(sprintf('we reindex %d users', $userCount));

        $groupCount = $this->em->getRepository(AddressGroup::class)->count([]);
        $progressBar = new ProgressBar($output, $groupCount);
        $progressBar->start();
        $lastId = 0;

        while (true) {
            $groups = $this->em->getRepository(AddressGroup::class)
                ->createQueryBuilder('g')
                ->where('g.id > :lastId')
                ->orderBy('g.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($groups) === 0) {
                break;
            }

            foreach ($groups as $data) {
                $lastId = $data->getId();
                $progressBar->advance();
                $data->setIndexer($this->groupIndexer->indexGroup($data));
                $this->em->persist($data);
            }

            $this->em->flush();
            $this->em->clear();
        }
        $progressBar->finish();
        $io->newLine();
        $io->success(sprintf('we reindex %d Groups', $groupCount));
        return Command::SUCCESS;
    }
}
