<?php

namespace App\Command\LobbyMessage;

use App\Entity\PredefinedLobbyMessages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:lobby:message:delete',
    description: 'Delete a lobby message by id',
)]
class LobbyMessageDeleteCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, 'Argument description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');

        if ($id) {
            $message = $this->entityManager->getRepository(PredefinedLobbyMessages::class)->find($id);
            if ($message) {
                $disableQ = new ConfirmationQuestion('Do you want to delete the message', false);
                $res = $io->askQuestion($disableQ);
                if ($res) {
                    $this->entityManager->remove($message);
                    $this->entityManager->flush();
                } else {
                    $io->success(sprintf('We haven`t deleted the message: %s', $message->getText()));
                    return Command::SUCCESS;
                }
            } else {
                $io->error('Wrong ID. no message found');
                return Command::FAILURE;
            }
        } else {
            $io->error('Please enter a valid id');
            return Command::FAILURE;
        }

        $io->success(sprintf('We delete the message: %s', $message->getText()));

        return Command::SUCCESS;
    }
}
