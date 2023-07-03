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
        foreach ($oldestData as $data) {
            if ($data->getCallerSession()) {
                if ($data->getCallerSession()->getCaller()) {
                    $data->getCallerSession()->setCaller(null);
                    $this->em->persist($data);
                }
                $this->em->remove($data->getCallerSession());
            }
            $this->em->remove($data);
        }
        $this->em->flush();
        return $oldestData;
    }
}
