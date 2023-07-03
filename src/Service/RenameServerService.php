<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class RenameServerService
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function renameServer($servers)
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
