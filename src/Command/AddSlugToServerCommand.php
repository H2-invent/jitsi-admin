<?php

namespace App\Command;

use App\Entity\Server;
use App\Service\ServerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:addSlugToServer')]
class AddSlugToServerCommand extends Command
{
    private $em;
    private $serverService;
    public function __construct(EntityManagerInterface $entityManager, ServerService $serverService, ?string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
        $this->serverService = $serverService;
    }

    protected function configure():void
    {
        $this
            ->setDescription('Adds a slug to all servers, which does not have a slug');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $servers = $this->em->getRepository(Server::class)
            ->createQueryBuilder('s')
            ->where('s.slug IS NULL')
            ->getQuery()
            ->getResult();

        foreach ($servers as $server) {
            $slug = $this->serverService->makeSlug($server->getUrl());
            $server->setSlug($slug);
            $this->em->persist($server);
            $io->writeln($slug);
        }
        $this->em->flush();

        $io->success('We transformed ' . count($servers) . ' Servers');

        return Command::SUCCESS;
    }
}
