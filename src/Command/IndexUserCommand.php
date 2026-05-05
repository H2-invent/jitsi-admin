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
    private $em;
    private $indexer;
    private $groupIndexer;
    protected function configure(): void
    {
    }

    public function __construct(EntityManagerInterface $entityManager, IndexUserService $indexUserService, IndexGroupsService $indexGroupsService, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
        $this->indexer = $indexUserService;
        $this->groupIndexer = $indexGroupsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $user = $this->em->getRepository(User::class)->findAll();
        $progressBar = new ProgressBar($output, sizeof($user));
        $progressBar->start();
        foreach ($user as $data) {
            $progressBar->advance();
            $data->setIndexer($this->indexer->indexUser($data));
            $this->em->persist($data);
        }
        $this->em->flush();
        $progressBar->finish();
        $io->success(sprintf('we reindex %d users', sizeof($user)));

        $group = $this->em->getRepository(AddressGroup::class)->findAll();
        $progressBar = new ProgressBar($output, sizeof($group));
        $progressBar->start();
        foreach ($group as $data) {
            $progressBar->advance();
            $data->setIndexer($this->groupIndexer->indexGroup($data));
            $this->em->persist($data);
        }
        $this->em->flush();
        $progressBar->finish();
        $io->newLine();
        $io->success(sprintf('we reindex %d Groups', sizeof($group)));
        return Command::SUCCESS;
    }
}
