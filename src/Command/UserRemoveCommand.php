<?php

namespace App\Command;

use App\Entity\User;
use App\Service\ldap\LdapUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserRemoveCommand extends Command
{
    protected static $defaultName = 'app:user:remove';
    protected static $defaultDescription = 'Removes a user by Username';
    private $em;
    private $ldapUserService;
    public function __construct(EntityManagerInterface $entityManager, LdapUserService $ldapUser,string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
        $this->ldapUserService = $ldapUser;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::OPTIONAL, 'Username of the User to delete')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        if ($username) {
            $io->note(sprintf('You passed an argument: %s', $username));
        }
        $user = $this->em->getRepository(User::class)->findOneBy(array('username'=>$username));
        $this->ldapUserService->deleteUser($user);

        $io->success(sprintf('Remove the User %s',$username));

        return Command::SUCCESS;
    }
}
