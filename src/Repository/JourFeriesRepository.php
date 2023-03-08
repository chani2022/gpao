<?php

namespace App\Repository;

use App\Entity\JourFeries;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method JourFeries|null find($id, $lockMode = null, $lockVersion = null)
 * @method JourFeries|null findOneBy(array $criteria, array $orderBy = null)
 * @method JourFeries[]    findAll()
 * @method JourFeries[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JourFeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JourFeries::class);
    }

    public function findAllFerie()
    {
        return $this->createQueryBuilder('j')
            ->orderBy('j.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
    // /**
    //  * @return JourFeries[] Returns an array of JourFeries objects
    //  */
    /*
    */

    /*
    public function findOneBySomeField($value): ?JourFeries
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
