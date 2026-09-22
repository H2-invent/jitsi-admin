<?php

namespace App\Service;

use App\Entity\LobbyWaitungUser;
use App\Repository\LobbyWaitungUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class CleanupLobbyService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @return LobbyWaitungUser[]
     */
    public function cleanUp(int|string $maxOld = 72): array
    {
        $date = (new \DateTimeImmutable())->modify('-' . $maxOld . 'hours');
        /** @var LobbyWaitungUserRepository $repo */
        $repo = $this->em->getRepository(LobbyWaitungUser::class);
        $oldestData = $repo->findOldLobbyWaitinguser($date);
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
