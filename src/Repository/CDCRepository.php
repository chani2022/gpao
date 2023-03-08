<?php

namespace App\Repository;

use App\Entity\CDC;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method CDC|null find($id, $lockMode = null, $lockVersion = null)
 * @method CDC|null findOneBy(array $criteria, array $orderBy = null)
 * @method CDC[]    findAll()
 * @method CDC[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CDCRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CDC::class);
    }

    /**
      * @return CDC[] Returns an array of CDC objects
      */
    
    public function search($kwd)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.nom_cdc LIKE :val OR c.observations LIKE :val2')
            ->setParameter('val', "%".strtoupper($kwd)."%")
            ->setParameter('val2', "%$kwd%")
            ->orderBy('c.nom_cdc', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?CDC
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
