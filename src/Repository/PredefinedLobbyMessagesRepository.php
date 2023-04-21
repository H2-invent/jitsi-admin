<?php

namespace App\Repository;

use App\Entity\PredefinedLobbyMessages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PredefinedLobbyMessages>
 *
 * @method PredefinedLobbyMessages|null find($id, $lockMode = null, $lockVersion = null)
 * @method PredefinedLobbyMessages|null findOneBy(array $criteria, array $orderBy = null)
 * @method PredefinedLobbyMessages[]    findAll()
 * @method PredefinedLobbyMessages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PredefinedLobbyMessagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PredefinedLobbyMessages::class);
    }

    public function add(PredefinedLobbyMessages $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PredefinedLobbyMessages $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PredefinedLobbyMessages[] Returns an array of PredefinedLobbyMessages objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PredefinedLobbyMessages
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
