<?php

namespace App\Repository;

use App\Entity\VisiteurTest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method VisiteurTest|null find($id, $lockMode = null, $lockVersion = null)
 * @method VisiteurTest|null findOneBy(array $criteria, array $orderBy = null)
 * @method VisiteurTest[]    findAll()
 * @method VisiteurTest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisiteurTestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VisiteurTest::class);
    }

    // /**
    //  * @return VisiteurTest[] Returns an array of VisiteurTest objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?VisiteurTest
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
