<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Service;

/**
 * Description of UserService
 *
 * @author dev
 */
class UserService {

    /**
     * @var \App\Model\GPAOModels\Personnel
     */
    private $pers;
    private $query;
    
    public function __construct(\Doctrine\DBAL\Connection $conn){
        $this->pers = new \App\Model\GPAOModels\Personnel($conn);
        $this->query = $this->pers->Get();
    }
    /**
     * 
     * @param array $fieldChoice
     */
    public function getQueryWithFieldToSelect(array $fieldChoice){
        //$personnel = new \App\Model\GPAOModels\Personnel($this->connexion);
        $this->query = $this->pers->Get($fieldChoice)
                           ->where('actif = :actif')
                           ->setParameter('actif', 'Oui')
                           ->andWhere('id_personnel > 0')
                           ->andWhere('nom_fonction IN(\'OP 1\',\'OP 2\',\'CORE 1\',\'CORE 2\')');
                           //->orderBy('id_personnel', "ASC")
                           //->execute()->fetchAll();
        
        return $this->query;
    }
    /**
     * tableau qui a pour clés le champ et la valeur est l'ordre
     * @param array $order
     * @return type
     */
    public function orderByUser(array $order){
        foreach($order as $key => $val){
            $this->query->orderBy($key, $val);
        }
        return $this->query;
    }
    /**
     * 
     * @return TOUS les utilisateur
     */
    public function fetchAllUser(){
        return $this->query->execute()->fetchAll();
    }
    /**
     * 
     * @return une utilisateur
     */
    public function fetchAOnUser(){
        return $this->query->execute()->fetchOne();
    }
}
