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
    public function __construct(private readonly EntityManagerInterface $em, private readonly ServerService $serverService, ?string $name = null)
    {
        parent::__construct($name);
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
        $server = $this->em->getRepository(Server::class)->findAll();
        $counter = 0;
        foreach ($server as $data) {
            if (!$data->getSlug()) {
                $counter++;

                $slug = $this->serverService->makeSlug($data->getUrl());
                $data->setSlug($slug);
                $this->em->persist($data);
                $io->writeln($slug);
                $this->em->flush();
            }
        }


        $io->success('We transformed ' . $counter . ' Servers');

        return Command::SUCCESS;
    }
}
