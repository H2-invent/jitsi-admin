<?php


namespace App\Service;


use App\Entity\EmailDomainsToServers;
use App\Entity\KeycloakGroupsToServers;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ServerUserManagment
{

    private $em;
    private $parameter;

    public function __construct(ParameterBagInterface $parameterBag, EntityManagerInterface $entityManager)
    {
        $this->parameter = $parameterBag;
        $this->em = $entityManager;
    }

    public function getServersFromUser(User $user)
    {
        $servers = array();
        //here we add theserver which is directed connected to a user
        $servers = $user->getServers()->toArray();
        $default = $this->em->getRepository(Server::class)->find($this->parameter->get('default_jitsi_server_id'));

        //here we add the default group which is set in the env
        if ($default && !in_array($default, $servers)) {
            $servers[] = $default;
        }

        // here we add the servers from thekeycloak group
        if ($user->getGroups()) {
            foreach ($user->getGroups() as $data1) {
                $tmpG = $this->em->getRepository(KeycloakGroupsToServers::class)->findBy(array('keycloakGroup' => $data1));
                foreach ($tmpG as $data2) {
                    if (!in_array($data2->getServer(), $servers)) {
                        $servers[] = $data2->getServer();
                    }
                }
            }
        }

        $domain = explode('@', $user->getEmail())[1];
        $tmpE = $this->em->getRepository(KeycloakGroupsToServers::class)->findBy(array('keycloakGroup' =>$domain ));
        foreach ($tmpE as $data2) {
            if (!in_array($data2->getServer(), $servers)) {
                $servers[] = $data2->getServer();
            }
        }

        return $servers;
    }
}
