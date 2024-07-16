<?php

namespace App\Command;

use App\Entity\CalloutSession;
use App\Repository\CalloutSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:callout:statistiks',
    description: 'Shows active callout stistiks',
)]
class CalloutStatistiksCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CalloutSessionRepository $calloutSessionRepository
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Zeigt eine Tabelle mit CalloutSessions an');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $calloutSessions = $this->calloutSessionRepository->findAll();

        $table = new Table($output);
        $table->setHeaders([ 'Room', 'User', 'Created At', 'Invited From',  'State', 'Left Retries', 'Last Dialed']);

        foreach ($calloutSessions as $session) {
            $table->addRow([

                $session->getRoom() ? $session->getRoom()->getName() : 'N/A',
                $session->getUser() ? $session->getUser()->getUsername() : 'N/A',
                $session->getCreatedAt() ? $session->getCreatedAt()->format('Y-m-d H:i:s') : 'N/A',
                $session->getInvitedFrom() ? $session->getInvitedFrom()->getUsername() : 'N/A',
                CalloutSession::$STATE[$session->getState()],
                $session->getLeftRetries(),
                $session->getLastDialed(),
            ]);
        }

        $table->render();

        return Command::SUCCESS;
    }
}
