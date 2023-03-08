<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Service;

/**
 * Description of ProductionService
 *
 * @author dev
 */
class ProductionService {

    /**
     * @var App\Model\GPAOModels\Production
     */
    private $prod;
    private $query;

    public function __construct(\Doctrine\DBAL\Connection $conn){
        $this->prod = new \App\Model\GPAOModels\Production($conn);
        $this->query = $this->prod->Get();
    }
    /**
     * permet de retourne une query avec des paramatres
     * @param array $field champ à selectionnez
     * @param array $critere critere de selection
     * @param string $operateur operateur or ou and
     * @return query
     */
    public function getQueryWithCritere(array $field = null, array $critere = null, string $operateur = null){
        
        $this->query = $this->prod->Get($field)
                                  ->innerJoin('fichiers','etape_travail','etape_travail', 'fichiers.id_etape_travail = etape_travail.id_etape_travail');
                                  //->where('date_traitement = :date_t')
                                  //->setParameter('date_t', (new \DateTime())->format("Y-m-d"));
        
        $whereBegin = false;
        $orCritere = [];
        
        foreach($critere as $key => $val){
            $f = explode(" = ", $key);
            $f = ltrim($f[1], ":");
            
            if(!$whereBegin){
                $whereBegin = true;
                $this->query->where($key)
                            ->setParameter($f, $val)
                            ->andWhere('date_traitement = :date_t')
                            ->setParameter('date_t', (new \DateTime())->format("Y-m-d"));
            }else{
                if($operateur == "AND"){
                    $this->query->andWhere($key)
                                ->setParameter($f, $val);
                }else{
                    $this->query->orWhere($key)
                                ->setParameter($f, $val);
                }
            }
        }
        
        return $this->query;
    }
    /**
     * Groupe by
     * @param string $field
     * @return string query
     */
    public function groupByProd(string $field){
        return $this->query->groupBy($field);
    }
    /**
     * execute et renvoie le resultat
     * @return array
     */
    public function fetchOneProd(){
        return $this->query->execute()->fetchOne();
    }
    /**
     * execute et renvoie tous les resultats
     * @return array
     */
    public function fetchAllProd(){
        return $this->query->execute()->fetchAll();
    }
    
}
