<?php

namespace App\Repository;

use App\Entity\SortieAvantHeure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method SortieAvantHeure|null find($id, $lockMode = null, $lockVersion = null)
 * @method SortieAvantHeure|null findOneBy(array $criteria, array $orderBy = null)
 * @method SortieAvantHeure[]    findAll()
 * @method SortieAvantHeure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortieAvantHeureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SortieAvantHeure::class);
    }

    // /**
    //  * @return SortieAvantHeure[] Returns an array of SortieAvantHeure objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SortieAvantHeure
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
