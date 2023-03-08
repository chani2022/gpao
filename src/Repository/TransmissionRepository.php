<?php

namespace App\Repository;

use App\Entity\Transmission;
use App\Entity\LectureTransmission;
use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Transmission|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transmission|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transmission[]    findAll()
 * @method Transmission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransmissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transmission::class);
    }

    // /**
    //  * @return Transmission[] Returns an array of Transmission objects
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
    
    /**
     * recherche message entrant
     */
    public function getMessageEntrant($destinataire_id, $Lecture=NULL, $dateDebut=NULL, $dateFin=NULL,$keywords=NULL,$expediteur_id=NULL,$sansReponses=NULL)
    {
        $obj = $this->createQueryBuilder('t');

        //jointure amin'ilay dossier tompon'ilay mail
        $obj->leftJoin('t.dossier','dossier');
        
        $obj->leftJoin('t.reponses','rep');
        

        if ($expediteur_id !== NULL){
            $obj->where('t.expediteur = :e')
            ->setParameter('e', $expediteur_id);

        }else{
            $obj->where('t.destinataires LIKE :val')
                ->setParameter('val', "%|".$destinataire_id."|%");
            //$obj->where('t.destinataires LIKE IN (:id_dest)')
            //    ->setParameter("id_dest", "%|".$destinataire_id."|%");
        }

        if ($sansReponses == TRUE){
            $obj->andWhere('rep.id is null');
        }
        
        if ($keywords!=NULL){
            $obj->andWhere('t.objet LIKE :kwd OR t.objet LIKE :kwd2 OR t.objet LIKE :kwd3 OR t.contenu LIKE :kwd OR t.contenu LIKE :kwd2 OR t.contenu LIKE :kwd3 OR dossier.nom_dossier LIKE :kwd OR dossier.nom_dossier LIKE :kwd2 OR dossier.nom_dossier LIKE :kwd3 OR dossier.nom_mairie LIKE :kwd OR dossier.nom_mairie LIKE :kwd3')
                    ->setParameter('kwd', "%".$keywords."%")
                    ->setParameter('kwd2', "%".ucfirst($keywords)."%")
                    ->setParameter('kwd3', "%".strtoupper($keywords)."%");
        }
        
        if ($dateDebut!=NULL){
            if ($dateFin==NULL || $dateFin == "") $dateFin = $dateDebut;
            
            $obj->andWhere('((t.date_envoie BETWEEN :debut and :fin) or (t.date_reel_reception BETWEEN :debut2 and :fin2) )')
                    ->setParameter('debut', $dateDebut." 00:00:00")
                    ->setParameter('fin', $dateFin." 23:59:00")
                    ->setParameter('debut2', $dateDebut)
                    ->setParameter('fin2', $dateFin)
                    ;
            
        }
        
        
        if ($Lecture === FALSE){
            $obj->andWhere("t.id NOT IN (SELECT IDENTITY(lt.transmission) from App\Entity\LectureTransmission lt WHERE lt.destinataire = :destLect)")
                ->setParameter("destLect", $destinataire_id);
        }
        
        $obj->orderBy('t.id','DESC');
            //->setMaxResults(10);//napik anty limite ty ilay iz
        
        return $obj->getQuery()->getResult();
    }

    /**
     * chargement message navette
     */
    public function getMessagesNavette($options)
    {
        $obj = $this->createQueryBuilder('t');

        //jointure amin'ilay dossier tompon'ilay mail
        $obj->leftJoin('t.dossier','dossier');
        
        $obj->leftJoin('t.reponses','rep');

        if (array_key_exists("dossier_id", $options)){
            $obj->where('t.dossier = :idDoss')->setParameter('idDoss',$options['dossier_id']);
        }

        if (array_key_exists("mailClient", $options)){
            $obj->where('t.mail_client = :mC')->setParameter('mC', $options['mailClient']);
        }
        
        if (array_key_exists("keywords", $options)){
            $keywords = $options['keywords'];
            $obj->andWhere('t.objet LIKE :kwd OR t.objet LIKE :kwd2 OR t.objet LIKE :kwd3 OR t.contenu LIKE :kwd OR t.contenu LIKE :kwd2 OR t.contenu LIKE :kwd3 OR dossier.nom_dossier LIKE :kwd OR dossier.nom_dossier LIKE :kwd2 OR dossier.nom_dossier LIKE :kwd3 OR dossier.nom_mairie LIKE :kwd OR dossier.nom_mairie LIKE :kwd3')
                    ->setParameter('kwd', "%".$keywords."%")
                    ->setParameter('kwd2', "%".ucfirst($keywords)."%")
                    ->setParameter('kwd3', "%".strtoupper($keywords)."%");
        }

        if (array_key_exists("sansReponses", $options)){
            if ($options['sansReponses'] == TRUE) $obj->andWhere('rep.id is null');
        }
        
        if (array_key_exists("dateDebut", $options)){
            $dateDebut = $options['dateDebut'];
            $dateFin = NULL;
            if (array_key_exists("dateFin", $options)) $dateFin = $options['dateFin'];


            if ($dateFin==NULL || $dateFin == "") $dateFin = $dateDebut;
            
            $obj->andWhere('((t.date_envoie BETWEEN :debut and :fin) or (t.date_reel_reception BETWEEN :debut2 and :fin2) )')
                    ->setParameter('debut', $dateDebut." 00:00:00")
                    ->setParameter('fin', $dateFin." 23:59:00")
                    ->setParameter('debut2', $dateDebut)
                    ->setParameter('fin2', $dateFin)
                    ;
            
        }
        
        $obj->orderBy('t.id','DESC');
        
        
        return $obj->getQuery()->getResult();
    }
    
}
