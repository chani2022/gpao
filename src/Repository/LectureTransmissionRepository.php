<?php

namespace App\Repository;

use App\Entity\LectureTransmission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method LectureTransmission|null find($id, $lockMode = null, $lockVersion = null)
 * @method LectureTransmission|null findOneBy(array $criteria, array $orderBy = null)
 * @method LectureTransmission[]    findAll()
 * @method LectureTransmission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LectureTransmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LectureTransmission::class);
    }

    // /**
    //  * @return LectureTransmission[] Returns an array of LectureTransmission objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LectureTransmission
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
