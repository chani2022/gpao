<?php
namespace App\Model\GPAOModels;

use App\Model\GPAODATAModel;

/**
 * Description of Personnel
 *
 * @author Administrateur
 */
class Personnel extends GPAODATAModel{

    //put your code here
    protected function getRelations() {
        return [
            //"\App\Model\GPAOModels\TypePointage"=>"id_type_pointage"
            "\App\Model\GPAOModels\EquipeTacheOperateur"=>"id_equipe_tache_operateur"
        ];
    }

    protected function getTable() {
        return "personnel";
    }
    
    public function getTransmissionUsers(){
        $data = $this->Get(array('id_personnel','nom','prenom','nom_fonction','login'))
                ->where('actif = :a and id_personnel> :id')
                ->setParameter('a','Oui')
                ->setParameter('id',0)
                ->andWhere('nom_privilege IN (:p,:ps)')
                ->setParameter('p','admin')
                ->setParameter('ps','superadmin')
                ->orderBy('id_personnel','ASC')
                ->execute()->fetchAll();
        
        $result = [];
        
        foreach($data as $d){
            $result[$d['id_personnel']] = $d;
        }
        
        return $result;
    }
    
    public function getListePersonnel($id=NULL) {
        $obj = $this->Get(array('id_personnel','nom','prenom','nom_fonction','login','date_embauche'));
        
        if (!is_null($id)){
            $obj->where('id_personnel = :id')
                ->setParameter('id',$id);
        }else{
            $obj->where('actif = :a and id_personnel> :id')
                ->setParameter('a','Oui')
                ->setParameter('id',2);
        }
                
        $obj->orderBy('id_personnel','ASC');
        
        return $obj->execute()->fetchAll();
    }

}
