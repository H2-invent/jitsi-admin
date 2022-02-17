<?php

namespace App\Command;

use App\Entity\KeycloakGroupsToServers;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConnectServerAndGroupsCommand extends Command
{
    protected static $defaultName = 'app:connectServerAndGroups';
    private $em;
    public function __construct( EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;

    }

    protected function configure()
    {
        $this
            ->setDescription('This connects a kecloak Group or a emaildomain with a server. Please add the server-Id, which can be found in the database and the keycloakgroup (on windows machines you need  two leading /all --> //all) or the domain of an email (info@example.com --> example.com)')
            ->addArgument('serverId', InputArgument::REQUIRED, 'This is the Server Id to connect to the keycloak Group')
            ->addArgument('keycloakGroup', InputArgument::REQUIRED, 'This is the keycloak Group or email domain. Alle members of this group can use the server to create Rooms');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $serverId = $input->getArgument('serverId');
        $keycloakGroup = $input->getArgument('keycloakGroup');
        $server = null;
        $server = $this->em->getRepository(Server::class)->find($serverId);
        if (!$server) {
            $io->error('This server is not available.');
            return Command::FAILURE;
        }
        $groupServer = $this->em->getRepository(KeycloakGroupsToServers::class)->findOneBy(array('server'=>$server,'keycloakGroup'=>$keycloakGroup));

        if ($groupServer){
            $io->error('This Server is already connected to this group');
            return Command::FAILURE;
        }
        $groupServer = new KeycloakGroupsToServers();
        $groupServer->setServer($server);
        $groupServer->setKeycloakGroup($keycloakGroup);
        $this->em->persist($groupServer);
        $this->em->flush();



        $io->success('We added the group '.$keycloakGroup.' to the server '.$server->getUrl());

        return Command::SUCCESS;
    }
}
