<?php

namespace App\Command;

use App\Repository\ServerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:server:allowToClone',
    description: 'Allow server to be used to auscale servers',
)]
class ServerAllowToCloneCommand extends Command
{
    public function __construct(
        private ServerRepository $serverRepository,
        private EntityManagerInterface $entityManager,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('server', InputArgument::OPTIONAL, 'Allow this server to be used a a prototype server to clone new servers for autoscale via api')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serverId = $input->getArgument('server');
        $server = $this->serverRepository->find($serverId);
        if ($server){
           $server->setAllowedToCloneForAutoscale(!$server->isAllowedToCloneForAutoscale());
        }

       $this->entityManager->persist($server);
        $this->entityManager->flush();
        if ($server->isAllowedToCloneForAutoscale()){
            $io->success('Server ALLOW to autoscale via api');
        }else{
            $io->success('Server DISALLOW to autoscale via api');
        }

        return Command::SUCCESS;
    }
}
