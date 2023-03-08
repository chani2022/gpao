<?php
 
namespace App\Model\GPAOModels;
use App\Model\GPAODATAModel;

class CategorieComptabilite extends GPAODATAModel{

    protected function getTable() {
        return "categorie_comptabilite";
    }

    protected function getRelations() {
        return [
        ];
    }

}