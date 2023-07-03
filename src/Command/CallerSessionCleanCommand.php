<?php

namespace App\Command;

use App\Entity\CallerSession;
use App\Service\caller\CallerSessionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:caller:session:clean',
    description: 'This Command is a special purpose command for a very specific use case. don`t use it until you are asked to do so, by the jitsi-admin support team',
)]
class CallerSessionCleanCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private CallerSessionService $callerSessionService, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //fetch all the session which are in the system
        $sessions = $this->entityManager->getRepository(CallerSession::class)->findAll();
        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders(['ID', 'Name', 'sessionId']);
        // show all sessions in a table
        foreach ($sessions as $data) {
            $table->addRow([$data->getId(), $data->getShowName(), $data->getSessionId()]);
        }
        $table->render();

        //ask the user to select a session i which he wants to delte
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the id of the session you want to delete: ', null);
        $id = $helper->ask($input, $output, $question);

        try {
            //find the session
            $session = $this->entityManager->getRepository(CallerSession::class)->find($id);
        } catch (\Exception $exception) {
            $io->error('No such ID');
            return Command::FAILURE;
        }


        if ($session) {//check if the session exists
            // confirm if the session should be deleted
            $question = new ConfirmationQuestion(sprintf('Do you realy want to delete this session of %s? (y/N)', $session->getShowName()), false, '/^(y|j)/i');
            if ($helper->ask($input, $output, $question)) { // if the user confirmed the deletion
                $this->callerSessionService->cleanUpSession($session);//delete the session via a service
                $io->success(sprintf('Delete Session %s from %s', $session->getSessionId(), $session->getShowName()));
                return Command::SUCCESS;
            } else {
                $io->info('NOT deleting the session');
            }
        } else {
            $io->error('No such ID');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
