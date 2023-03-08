<?php

namespace App\Repository;

use App\Entity\TransmissionPieceJointe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TransmissionPieceJointe|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransmissionPieceJointe|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransmissionPieceJointe[]    findAll()
 * @method TransmissionPieceJointe[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransmissionPieceJointeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransmissionPieceJointe::class);
    }

    // /**
    //  * @return TransmissionPieceJointe[] Returns an array of TransmissionPieceJointe objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TransmissionPieceJointe
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
