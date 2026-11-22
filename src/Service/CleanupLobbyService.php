<?php

namespace App\Service;

use App\Entity\LobbyWaitungUser;
use App\Repository\LobbyWaitungUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class CleanupLobbyService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @return LobbyWaitungUser[]
     */
    public function cleanUp(int|string $maxOld = 72): array
    {
        $date     = new \DateTimeImmutable()->modify('-' . $maxOld . 'hours');
        $sessions = [];

        /** @var LobbyWaitungUserRepository $repo */
        $repo       = $this->em->getRepository(LobbyWaitungUser::class);
        $oldestData = $repo->findOldLobbyWaitinguser($date);
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
