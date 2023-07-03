<?php

namespace App\Service\Deputy;

use App\Entity\Deputy;
use Doctrine\ORM\EntityManagerInterface;

class DebutyLdapService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return int
     */
    public function cleanDeputies($dryRun = false)
    {
        $counter = 0;
        $deputies = $this->entityManager->getRepository(Deputy::class)->findBy(['isFromLdap' => true]);

        foreach ($deputies as $data) {
            $this->entityManager->remove($data);
            $counter++;
        }
        if (!$dryRun) {
            $this->entityManager->flush();
        } else {
            $this->entityManager->clear();
        }

        return $counter;
    }
}
