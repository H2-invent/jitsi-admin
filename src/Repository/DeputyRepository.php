<?php

namespace App\Repository;

use App\Entity\Deputy;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deputy>
 *
 * @method Deputy|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deputy|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deputy[]    findAll()
 * @method Deputy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeputyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deputy::class);
    }

    public function save(Deputy $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Deputy $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Returns all Deputy relations for the given manager, indexed by the deputy user id.
     *
     * @return array<int, Deputy>
     */
    public function findForManager(User $manager): array
    {
        $result = [];
        foreach ($this->findBy(['manager' => $manager]) as $dep) {
            $deputy = $dep->getDeputy();
            if ($deputy !== null) {
                $result[$deputy->getId()] = $dep;
            }
        }

        return $result;
    }
}
