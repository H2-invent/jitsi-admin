<?php

namespace App\Command\LobbyMessage;

use App\Entity\PredefinedLobbyMessages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:lobby:message:changeText',
    description: 'Change the text of a lobby message',
)]
class LobbyMessageChangeTextCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, 'Insert message id change text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');

        if ($id) {
            $message = $this->entityManager->getRepository(PredefinedLobbyMessages::class)->find($id);
            if ($message) {
                $io->note(sprintf('We edit the message: %s', $message->getText()));
                $textQ = new Question('Enter the message text: ', $message->getText());
                $message->setText($io->askQuestion($textQ));
                $this->entityManager->persist($message);
                $this->entityManager->flush();
            } else {
                $io->error('Wrong ID. no message found');
                return Command::FAILURE;
            }
        } else {
            $io->error('Please enter a valid id');
            return Command::FAILURE;
        }


        $io->success(sprintf('You have changed the message to %s', $message->getText()));

        return Command::SUCCESS;
    }
}
