<?php

namespace App\Repository;

use App\Entity\IncidentGeneral;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method IncidentGeneral|null find($id, $lockMode = null, $lockVersion = null)
 * @method IncidentGeneral|null findOneBy(array $criteria, array $orderBy = null)
 * @method IncidentGeneral[]    findAll()
 * @method IncidentGeneral[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IncidentGeneralRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncidentGeneral::class);
    }
    
    public function findAllIncident($keyword = null){
        
        return $this->createQueryBuilder("inc")
                    ->getQuery();
    }
    public function incidentOrderBy(){
        return $this->createQueryBuilder("inc")
                    ->orderBy("inc.date_incident", "DESC")
                    ->getQuery()->getResult();
    }

    // /**
    //  * @return IncidentGeneral[] Returns an array of IncidentGeneral objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?IncidentGeneral
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
