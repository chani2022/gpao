<?php

namespace App\Repository;

use App\Entity\RecolteHeure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method RecolteHeure|null find($id, $lockMode = null, $lockVersion = null)
 * @method RecolteHeure|null findOneBy(array $criteria, array $orderBy = null)
 * @method RecolteHeure[]    findAll()
 * @method RecolteHeure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RecolteHeureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecolteHeure::class);
    }

    // /**
    //  * @return RecolteHeure[] Returns an array of RecolteHeure objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RecolteHeure
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
