<?php

namespace App\Command\LobbyMessage;

use App\Entity\PredefinedLobbyMessages;
use App\Repository\PredefinedLobbyMessagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:lobby:message:create',
    description: 'This commend creates a messages which could be send from a lobbymoderator to a waiting participant',
)]
class LobbyMessageCreateCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private PredefinedLobbyMessagesRepository $predefinedLobbyMessagesRepository, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('text', InputArgument::OPTIONAL, 'Enter the text you want to send to the waiting user')
            ->addArgument('prio', InputArgument::OPTIONAL, 'Enter the priority of the message. Lower will be show first');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $text = $input->getArgument('text');

        if ($text) {
            $io->note(sprintf('We create a new Predefined message with the text: %s', $text));
            $messageOld = $this->predefinedLobbyMessagesRepository->findOneBy(array('text' => $text));
            if ($messageOld){
                $io->error('The message is already defined');

                return Command::FAILURE;
            }
        } else {
            $textQ = new Question('Enter the message text: ', 'Please wait. I will let you in in some minutes.');
            $text = $io->askQuestion($textQ);
        }


        $message = new PredefinedLobbyMessages();
        $message->setText($text)
            ->setCreatedAt(new \DateTime());
        $prio = $input->getArgument('prio');
        if ($prio) {
            $io->note(sprintf('We create a new Predefined message with the prio: %d', $prio));
            $message->setActive(true);
        } else {
            $prioQ = new Question('Enter the Priority (The Lowest will be shown first and is the default)', 0);
            $prio = $io->askQuestion($prioQ);
            $disableQ = new ConfirmationQuestion('Do you want to Enable the message', true);
            $message->setActive($io->askQuestion($disableQ));
        }

        $message->setPriority(intval($prio));


        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $io->success('We create a new Predefined message. This message can now be send from the moderator to a waiting lobby user.');

        return Command::SUCCESS;
    }
}
