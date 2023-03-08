<?php

namespace App\Repository;

use App\Entity\Navette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Navette|null find($id, $lockMode = null, $lockVersion = null)
 * @method Navette|null findOneBy(array $criteria, array $orderBy = null)
 * @method Navette[]    findAll()
 * @method Navette[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NavetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Navette::class);
    }
    
    
    public function getRecentlyAds($dossier,$nb=5)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.dossier = :val')
            ->setParameter('val', $dossier)
            ->orderBy('n.date_envoie', 'DESC')
            ->setMaxResults($nb)
            ->getQuery()
            ->getResult()
        ;
    }
    

    // /**
    //  * @return Navette[] Returns an array of Navette objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Navette
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
