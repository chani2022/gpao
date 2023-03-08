<?php

namespace App\Repository;

use App\Entity\Visiteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\DateImmutableType;

/**
 * @method Visiteur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Visiteur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Visiteur[]    findAll()
 * @method Visiteur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VisiteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visiteur::class);
    }


    public function searchVisiteur($data)
    {
        /**
         * creer une date de debut qui a pour parametre la date de recherche
         *
         * $dateD = new \DateTime($data->format("Y-m-d"));
         *
         * $qb->where("v.heureentrer BETWEEN :dateD and :dateF")
         * ->setParameter('dateD', $dateD)
         * ->setParameter('dateF', $dateF);
         *
         * }else{
         * $qb->where('v.prenom LIKE :prenom')
         * ->orWhere('v.nom LIKE :nom')
         * ->orWhere('v.cin LIKE :cin')
         * ->setParameter('nom', '%'.strtoupper($data).'%')
         * ->setParameter('prenom', '%'.ucwords($data).'%')
         * ->setParameter('cin', '%'.$data.'%');
         * }
         * $query = $qb->getQuery();
         * return $query->getResult();
         **/
        return $this->createQueryBuilder('v')
            ->where('v.nom LIKE :nom')
            ->orWhere('v.prenom LIKE :prenom')
            ->orWhere('v.cin LIKE :cin')
            ->setParameter('nom', '%' . strtoupper($data) . '%')
            ->setParameter('prenom', '%' . ucwords($data) . '%')
            ->setParameter('cin', '%' . $data . '%')
            ->getQuery()
            ->getResult();

    }
    public function findDataHierAndToDate(){
        $hier= mktime(0, 0, 0, date("m") , date("d") -1, date("Y"));
        $hier_date= date("Y-m-d", $hier);

        $now = new \DateTime();

        return $this->createQueryBuilder('u')
                ->where("u.heureentrer >= :debut")
                ->andWhere("u.heureentrer <= :fin")
                ->setParameter("debut", $hier_date)
                ->setParameter("fin", $now)
                ->getQuery()
                ->getResult();
    }

    public function searchByDate($debut, $fin)
    {
        $qb = $this->createQueryBuilder('v');

        $fin = new \DateTime($fin);
        $fin->setTime(23, 59, 59);
        /**
         * creer une date de debut qui a pour parametre la date de recherche
         */
        $debut = new \DateTime($debut);


        $qb->where("v.heureentrer >= :dateD")
            ->andWhere("v.heureentrer <= :dateF")
            ->setParameter('dateD', $debut)
            ->setParameter('dateF', $fin);

        $query = $qb->getQuery();
        return $query->getResult();
    }


    /*
    public function findOneBySomeField($value): ?Visiteur
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
