<?php

namespace App\Service;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;

class RenameServerService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * @param Server[] $servers
     * @return Server[]
     */
    public function renameServer(array $servers): array
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
