<?php

namespace App\Command;

use App\Controller\DashboardController;
use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class SystemRepairCommand extends Command
{
    protected static $defaultName = 'app:system:repair';
    protected static $defaultDescription = 'Add a short description for your command';
    private $em;


    public function __construct(EntityManagerInterface $entityManager,string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;

    }

    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('We try to repair the system.....');
        $count = 0;
        $rooms =$this->em->getRepository(Rooms::class)->findAll();

        foreach ($rooms as $room){
            if(!$room->getModerator() || !$room->getServer()){
                foreach ($room->getUser() as $user){
                    $count++;
                    $room->removeUser($user);
                }
                $this->em->persist($room);
            }
        }
        $this->em->flush();
        $lobbyWaitingUser = $this->em->getRepository(LobbyWaitungUser::class)->findAll();
        foreach ($lobbyWaitingUser as $waitingUser){
            if($waitingUser->getCreatedAt() < (new \DateTime())->modify('-10days')){
                $count++;
                $this->em->remove($waitingUser);
            }
        }
        $this->em->flush();
        $user = $this->em->getRepository(User::class)->findAll();
        $count += $this->repairWaitungUser();
        $io->success(sprintf('We found %d coruppt datasets',$count));

        return Command::SUCCESS;
    }

    private function repairWaitungUser(){
        $waitingUser =$this->em->getRepository(LobbyWaitungUser::class)->findAll();
        $count = 0;
        foreach ($waitingUser as $data){
            try {
               $session = $data->getCallerSession();
            }catch (\Exception $exception){
                $this->em->remove($data);
                $count++;
            }
        }
        $this->em->flush();
        return $count;
    }
}
