<?php

namespace App\Service;

use App\Entity\LobbyWaitungUser;
use Doctrine\ORM\EntityManagerInterface;

class CleanupLobbyService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function cleanUp($maxOld = 72)
    {
        $date = (new \DateTime())->modify('-' . $maxOld . 'hours');
        $oldestData = $this->em->getRepository(LobbyWaitungUser::class)->findOldLobbyWaitinguser($date);
        $sessions = [];

        foreach ($oldestData as $data) {
            if ($data->getCallerSession()) {
                $session = $data->getCallerSession();
                $session->setCaller(null);
                $session->setLobbyWaitingUser(null);
                $sessions[] = $session;
            }
        }

        // Persist FK nullification before deleting either side of the relation.
        $this->em->flush();

        foreach ($oldestData as $data) {
            $this->em->remove($data);
        }

        foreach ($sessions as $session) {
            $this->em->remove($session);
        }

        $this->em->flush();
        return $oldestData;
    }
}
