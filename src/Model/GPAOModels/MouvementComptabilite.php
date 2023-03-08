<?php
 
namespace App\Model\GPAOModels;
use App\Model\GPAODATAModel;

class MouvementComptabilite extends GPAODATAModel{

    protected function getTable() {
        return "mouvement_comptabilite";
    }

    protected function getRelations() {
        return [
        	"\App\Model\GPAOModels\CategorieComptabilite"=>"id_categorie_comptabilite",
        ];
    }

}