<?php

namespace App\Service;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;

class RenameServerService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param Server[] $servers
     * @return Server[]
     */
    public function renameServer($servers): array
    {
        $res = [];
        foreach ($servers as $data) {
            if ($data->getServerName() === '' || $data->getServerName() === null) {
                $data->setServerName($data->getUrl());
                $this->em->persist($data);
                $res[] = $data;
            }
        }
        $this->em->flush();
        return $res;
    }
}
