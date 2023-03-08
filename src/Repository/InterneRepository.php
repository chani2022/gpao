<?php

namespace App\Repository;

use App\Entity\Interne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Interne|null find($id, $lockMode = null, $lockVersion = null)
 * @method Interne|null findOneBy(array $criteria, array $orderBy = null)
 * @method Interne[]    findAll()
 * @method Interne[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interne::class);
    }

    public function searchInterne($data)
    {
        /**
         * creer une date de debut qui a pour parametre la date de recherche
         *
         * $dateD = new \DateTime($data->format("Y-m-d"));
         *
         * $qb->where("v.dates BETWEEN :dateD and :dateF")
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
            ->where('v.donneurOrdre LIKE :donneur')
            ->orWhere('v.matricule LIKE :matricule')
            ->setParameter('donneur', '%' . $data . '%')
            ->setParameter('matricule', '%' . $data . '%')
            ->getQuery()
            ->getResult();

    }
    public function findDataHierAndToDate(){
        $hier= mktime(0, 0, 0, date("m") , date("d") -1, date("Y"));
        $hier_date= date("Y-m-d", $hier);
        $now = new \DateTime();

        return $this->createQueryBuilder('u')
            ->where("u.dates >= :debut")
            ->andWhere("u.dates <= :fin")
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


        $qb->where("v.dates >= :dateD")
            ->andWhere("v.dates <= :dateF")
            ->setParameter('dateD', $debut)
            ->setParameter('dateF', $fin);
        /**
         * ->setParameter('dateD', $debut)
         * ->setParameter('dateF', $fin)
         **/
        $query = $qb->getQuery();
        return $query->getResult();
    }
    /**
     * creer une date de debut qui a pour parametre la date de recherche
     *
     * $dateD = new \DateTime($data->format("Y-m-d"));
     *
     * $qb->where("v.dates BETWEEN :dateD and :dateF")
     * ->setParameter('dateD', $dateD)
     * ->setParameter('dateF', $dateF);
     * $query = $qb->getQuery();
     * return $query->getResult();
     * }
     **/
    // /**
    //  * @return Interne[] Returns an array of Interne objects
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
    public function findOneBySomeField($value): ?Interne
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
