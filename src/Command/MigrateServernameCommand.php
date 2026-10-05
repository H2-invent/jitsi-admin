<?php

namespace App\Command;

use App\Entity\Server;
use App\Service\RenameServerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[\Symfony\Component\Console\Attribute\AsCommand('app:migrate:servername', 'This command adds the server url as server name. This only happens when the entry is empty or null')]
class MigrateServernameCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly RenameServerService $serverRename, ?string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $server = $this->em->getRepository(Server::class)->findAll();
        $res = $this->serverRename->renameServer($server);
        foreach ($res as $data) {
            $io->info(sprintf('We rename the server with the url %s', $data->getUrl()));
        }
        $io->success(sprintf('We rename # %u of servers', sizeof($res)));
        return Command::SUCCESS;
    }
}
