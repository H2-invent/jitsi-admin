<?php

namespace App\Service\Deputy;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DebutyLdapService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return int
     */
    public function cleanDeputies($dryRun=false)
    {
        $counter = 0;
        $user = $this->entityManager->getRepository(User::class)->findUsersWithDeputy();
        foreach ($user as $data) {
            if ($data instanceof User) {
                foreach ($data->getDeputy() as $data2) {
                    $data->removeDeputy($data2);
                    $counter++;
                }
                $this->entityManager->persist($data);
            }
        }
        if (!$dryRun){
            $this->entityManager->flush();
        }else{
            $this->entityManager->clear();
        }

        return $counter;
    }
}