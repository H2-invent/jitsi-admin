<?php

namespace App\Service;

use App\Entity\EmailDomainsToServers;
use App\Entity\KeycloakGroupsToServers;
use App\Entity\Rooms;
use App\Entity\RoomStatusParticipant;
use App\Entity\Server;
use App\Entity\User;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ServerUserManagment
{
    private $em;
    private $parameter;
    private ThemeService $themeService;

    public function __construct(ThemeService $themeService, ParameterBagInterface $parameterBag, EntityManagerInterface $entityManager)
    {
        $this->parameter = $parameterBag;
        $this->em = $entityManager;
        $this->themeService = $themeService;
    }

    /**
     * @param User $user
     * @return Server[]
     * Return the Server for an User. This can be
     * individual server
     * default server for all users
     * keycloakserver by group or domain
     */
    public function getServersFromUser(User $user)
    {
        $servers = $user->getServers()->toArray();

        $searchTerms = [];
        if ($user->getGroups()) {
            foreach ($user->getGroups() as $group) {
                $searchTerms[] = $group;
            }
        }
        try {
            $domainArr = explode('@', $user->getEmail());
            if (count($domainArr) > 1) {
                $searchTerms[] = explode('@', $user->getEmail())[1];
            }
        } catch (\Exception $exception) {
        }

        if (!empty($searchTerms)) {
            $tmpResults = $this->em->getRepository(KeycloakGroupsToServers::class)->findBy(['keycloakGroup' => $searchTerms]);
            foreach ($tmpResults as $mapping) {
                if (!in_array($mapping->getServer(), $servers)) {
                    $servers[] = $mapping->getServer();
                }
            }
        }

        $default = $this->em->getRepository(Server::class)->find($this->parameter->get('default_jitsi_server_id'));
        //here we add the default group which is set in the env
        if ($default && !in_array($default, $servers)) {
            $servers[] = $default;
        }

        try {
            if ($this->themeService->getTheme()) {
                $sTmp = $this->themeService->getTheme()['showServer'];
                if (sizeof($sTmp) === 0) {
                    return $servers;
                }
                foreach ($user->getServers() as $data) {
                    if (!in_array($data->getId(), $sTmp)) {
                        $sTmp[] = $data->getId();
                    }
                }
                $serTmp = [];
                foreach ($servers as $data) {
                    if (in_array($data->getId(), $sTmp)) {
                        $serTmp[] = $data;
                    }
                }
                $servers = $serTmp;
                $serTmp = [];

                if ($this->themeService->getTheme()['showOnlyShowServer']) {
                    $sTmp = $this->themeService->getTheme()['showServer'];
                    foreach ($servers as $data) {
                        if (in_array($data->getId(), $sTmp)) {
                            $serTmp[] = $data;
                        }
                    }
                    $servers = $serTmp;
                }
            }
        } catch (\Exception $exception) {
        }

        return $servers;
    }

    public function getActualConference(Server $server)
    {
        return $this->em->getRepository(Rooms::class)->findActualConferenceForServerByStatus($server);
    }

    public function getActualParticipantsFromServer(Server $server)
    {
        return $this->em->getRepository(RoomStatusParticipant::class)->findActualParticipantsByServer($server);
    }
}
