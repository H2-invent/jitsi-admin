<?php

namespace App\Command\LobbyMessage;

use App\Entity\PredefinedLobbyMessages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:lobby:message:list',
    description: 'This commend lists all messages which are predefined and could be send from a lobbymoderator to a waiting participant',
)]
class LobbyMessageListCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Active', 'Text', 'Prio']);


        $messages = $this->entityManager->getRepository(PredefinedLobbyMessages::class)->findBy([], ['priority' => 'ASC']);

        foreach ($messages as $data) {
            if ($data->isActive()) {
                $table->addRow([$data->getId(), '[X]', $data->getText(), $data->getPriority()]);
            } else {
                $table->addRow([$data->getId(), '[ ]', $data->getText(), $data->getPriority()]);
            }
        }
        $table->render();
        $io->success('This are all you messages.');

        return Command::SUCCESS;
    }
}
