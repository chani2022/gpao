<?php

namespace App\Repository;

use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Dossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dossier[]    findAll()
 * @method Dossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }
    
    public function search($kwd,$dates = null)
    {

        $qb = $this     ->createQueryBuilder('d')
                        ->andWhere('d.nom_dossier LIKE :val or d.nom_mairie LIKE :val')
                        ->setParameter('val', "%".strtoupper($kwd)."%")
                        ->orderBy('d.nom_dossier', 'ASC');
        if (!is_null($dates)) {
            $qb     ->andWhere('d.date_ajout BETWEEN :start_date AND :end_date')
                    ->setParameter('start_date', $dates[0])
                    ->setParameter('end_date', $dates[1]);
        }

        return $qb  ->getQuery()
                    ->getResult();
    }

    // /**
    //  * @return Dossier[] Returns an array of Dossier objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Dossier
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
